<?php

namespace App\Services;

use App\Enums\FinancialCashDirection;
use App\Enums\FinancialHandoverStatus;
use App\Enums\FinancialPaymentMethod;
use App\Enums\FinancialReceiptStatus;
use App\Models\FinancialReceipt;
use App\Models\ServiceTransaction;
use App\Models\User;
use App\Services\Financial\Contracts\FinancialReceiptSourceConnector;
use App\Services\Financial\FinancialReceiptConnectorRegistry;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FinancialReceiptService
{
    /**
     * Register cash as employee custody and verify other payment methods separately.
     *
     * @param  array<string, mixed>  $data
     */
    public static function record(ServiceTransaction|Model $source, array $data, ?User $user = null): FinancialReceipt
    {
        $sourceType = $source instanceof ServiceTransaction
            ? FinancialReceipt::SOURCE_SERVICE_TRANSACTION
            : (method_exists($source, 'financialSourceType')
                ? $source->financialSourceType()
                : throw ValidationException::withMessages(['source_type' => __('financial.errors.unsupported_source')]));

        return static::recordFor($sourceType, (int) $source->getKey(), $data, $user);
    }

    /**
     * Register a receipt against any registered source connector.
     *
     * @param  array<string, mixed>  $data
     */
    public static function recordFor(string $sourceType, int $sourceId, array $data, ?User $user = null): FinancialReceipt
    {
        $connector = FinancialReceiptConnectorRegistry::for($sourceType);
        $source = $connector->find($sourceId);
        $user = $connector->authorize($source, 'record', $user);
        $connector->assertManaged($source);

        $amount = round((float) ($data['amount'] ?? 0), 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => __('financial.errors.invalid_amount'),
            ]);
        }

        $method = $data['payment_method'] ?? FinancialPaymentMethod::CASH;
        if (is_string($method)) {
            $method = FinancialPaymentMethod::tryFrom($method);
        }

        if (! $method instanceof FinancialPaymentMethod) {
            throw ValidationException::withMessages([
                'payment_method' => __('financial.errors.invalid_payment_method'),
            ]);
        }

        $requestKey = trim((string) ($data['request_key'] ?? ''));
        if ($requestKey === '') {
            throw ValidationException::withMessages([
                'request_key' => __('financial.errors.request_key_required'),
            ]);
        }

        $collectorId = $connector->defaultCollectorEmployeeId($source, $user, $data);

        if ($method->isCash() && empty($collectorId)) {
            throw ValidationException::withMessages([
                'collector_employee_id' => __('financial.errors.collector_required'),
            ]);
        }

        $payerName = $connector->defaultPayerName($source, $data);
        if ($method->isCash() && empty($payerName)) {
            throw ValidationException::withMessages([
                'payer_name' => __('financial.errors.payer_required'),
            ]);
        }

        $fingerprint = static::fingerprint([
            'source' => $sourceType.':'.$sourceId,
            'amount' => $amount,
            'method' => $method->value,
            'collector' => $collectorId,
            'reference' => $data['reference_number'] ?? null,
        ]);

        $companyId = $connector->companyId($source);

        return DB::transaction(function () use ($connector, $companyId, $sourceType, $sourceId, $data, $user, $amount, $method, $requestKey, $collectorId, $payerName, $fingerprint) {
            DB::table('companies')->where('id', $companyId)->lockForUpdate()->first();
            $lockedSource = $connector->findLocked($sourceId);
            $existing = FinancialReceipt::where('request_key', $requestKey)->lockForUpdate()->first();

            if ($existing) {
                if (($existing->metadata['fingerprint'] ?? null) !== $fingerprint) {
                    throw ValidationException::withMessages([
                        'request_key' => __('financial.errors.request_key_reused'),
                    ]);
                }

                return $existing;
            }

            $isCash = $method->isCash();
            $connector->validateRecordAmount($lockedSource, $amount);

            $receipt = FinancialReceipt::create([
                'company_id' => $connector->companyId($lockedSource),
                'branch_id' => $connector->branchId($lockedSource),
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'request_key' => $requestKey,
                'collector_employee_id' => $collectorId ?: null,
                'payer_name' => $payerName,
                'amount' => $amount,
                'payment_method' => $method->value,
                'status' => $isCash ? FinancialReceiptStatus::CONFIRMED->value : FinancialReceiptStatus::PENDING_CONFIRMATION->value,
                'reference_number' => $data['reference_number'] ?? null,
                'proof_path' => $data['proof_path'] ?? null,
                'destination_type' => $data['destination_type'] ?? null,
                'destination_reference' => $data['destination_reference'] ?? null,
                'received_at' => $data['received_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
                'confirmed_by' => $isCash ? $user->id : null,
                'confirmed_at' => $isCash ? now() : null,
                'metadata' => array_merge($data['metadata'] ?? [], [
                    'fingerprint' => $fingerprint,
                    'confirmed_item_amounts' => [],
                ]),
            ]);

            $connector->onReceiptRecorded($lockedSource, $receipt, $user);

            if ($isCash) {
                static::afterConfirmation($connector, $lockedSource, $receipt, $user);
            } elseif (! empty($data['confirm'])) {
                return static::confirm($receipt, [], $user);
            }

            return $receipt->refresh();
        });
    }

    /**
     * Confirm a receipt after verifying its destination and proof when needed.
     *
     * @param  array<string, mixed>  $data
     */
    public static function confirm(FinancialReceipt $receipt, array $data = [], ?User $user = null): FinancialReceipt
    {
        $connector = FinancialReceiptConnectorRegistry::for((string) $receipt->source_type);
        $source = $connector->find((int) $receipt->source_id);
        $user = $connector->authorize($source, 'confirm', $user);

        return DB::transaction(function () use ($connector, $source, $receipt, $data, $user) {
            DB::table('companies')->where('id', $receipt->company_id)->lockForUpdate()->first();
            $lockedSource = $connector->findLocked((int) $receipt->source_id);
            $locked = FinancialReceipt::whereKey($receipt->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === FinancialReceiptStatus::CONFIRMED) {
                return $locked;
            }

            if ($locked->status === FinancialReceiptStatus::REVERSED) {
                throw ValidationException::withMessages([
                    'status' => __('financial.errors.receipt_reversed'),
                ]);
            }

            $connector->validateConfirmAmount($lockedSource, $locked);

            $reference = $data['reference_number'] ?? $locked->reference_number;
            $proof = $data['proof_path'] ?? $locked->proof_path;
            $destinationType = $data['destination_type'] ?? $locked->destination_type;
            $destinationReference = $data['destination_reference'] ?? $locked->destination_reference;

            if (! $locked->isCash()) {
                if (empty($destinationType) || empty($destinationReference) || empty($reference) || empty($proof)) {
                    throw ValidationException::withMessages([
                        'destination_type' => __('financial.errors.non_cash_destination_required'),
                    ]);
                }
            }

            $meta = $locked->metadata ?? [];

            if (! $locked->isCash() && empty($meta['treasury_transaction_id'])) {
                $treasuryTx = FinancialTreasuryService::post(
                    companyId: (int) $locked->company_id,
                    direction: FinancialCashDirection::INCOMING,
                    amount: (float) $locked->amount,
                    description: __('financial.descriptions.non_cash_receipt_confirmed', [
                        'number' => $locked->receipt_number,
                    ]),
                    metadata: [
                        'source' => 'financial_receipt',
                        'financial_receipt_id' => $locked->id,
                        'receipt_number' => $locked->receipt_number,
                        'source_type' => $locked->source_type,
                        'source_id' => $locked->source_id,
                        'payment_method' => $locked->payment_method?->value,
                        'reference_number' => $reference,
                        'destination_type' => $destinationType,
                        'destination_reference' => $destinationReference,
                    ],
                    executedByEmployeeId: $locked->collector_employee_id,
                    counterparty: $locked->payer_name,
                    category: $connector->handoverCategory(),
                );

                $meta['treasury_transaction_id'] = $treasuryTx->id;
            }

            $locked->update([
                'status' => FinancialReceiptStatus::CONFIRMED->value,
                'confirmed_by' => $user->id,
                'confirmed_at' => now(),
                'reference_number' => $reference,
                'proof_path' => $proof,
                'destination_type' => $destinationType,
                'destination_reference' => $destinationReference,
                'metadata' => $meta,
            ]);

            static::afterConfirmation($connector, $lockedSource, $locked, $user);

            return $locked->refresh();
        });
    }

    private static function afterConfirmation(
        FinancialReceiptSourceConnector $connector,
        Model $source,
        FinancialReceipt $receipt,
        User $user,
    ): void {
        $connector->onReceiptConfirmed($source, $receipt, $user);
        static::fulfillPendingHandoverIntent($receipt);
    }

    public static function reverse(FinancialReceipt $receipt, string $reason, ?User $user = null): FinancialReceipt
    {
        $connector = FinancialReceiptConnectorRegistry::for((string) $receipt->source_type);
        $source = $connector->find((int) $receipt->source_id);
        $user = $connector->authorize($source, 'reverse', $user);

        $wasReversed = false;

        $receipt = DB::transaction(function () use ($connector, $receipt, $reason, $user, &$wasReversed) {
            DB::table('companies')->where('id', $receipt->company_id)->lockForUpdate()->first();
            $connector->findLocked((int) $receipt->source_id);
            $locked = FinancialReceipt::whereKey($receipt->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === FinancialReceiptStatus::REVERSED) {
                return $locked;
            }

            if ($locked->status !== FinancialReceiptStatus::CONFIRMED) {
                throw ValidationException::withMessages([
                    'status' => __('financial.errors.only_confirmed_receipt_can_be_reversed'),
                ]);
            }

            if ($locked->isAllocatedTreasuryCash()) {
                throw ValidationException::withMessages([
                    'status' => __('financial.errors.reverse_surplus_allocation_first'),
                ]);
            }

            $inOpenHandover = $locked->handoverItems()
                ->whereHas('handover', fn ($query) => $query->whereIn('status', [
                    FinancialHandoverStatus::SUBMITTED->value,
                    FinancialHandoverStatus::CONFIRMED->value,
                ]))
                ->exists();

            if ($inOpenHandover) {
                throw ValidationException::withMessages([
                    'status' => __('financial.errors.receipt_in_handover'),
                ]);
            }

            $meta = $locked->metadata ?? [];
            $meta['reversal_reason'] = $reason;
            $meta['reversed_at'] = now()->toDateTimeString();
            $meta['reversed_by'] = $user?->id ?? auth()->id();

            if (! $locked->isCash()
                && ! empty($meta['treasury_transaction_id'])
                && empty($meta['reversal_treasury_transaction_id'])) {
                $reversalTx = FinancialTreasuryService::post(
                    companyId: (int) $locked->company_id,
                    direction: FinancialCashDirection::OUTGOING,
                    amount: (float) $locked->amount,
                    description: __('financial.descriptions.non_cash_receipt_reversed', [
                        'number' => $locked->receipt_number,
                    ]),
                    metadata: [
                        'source' => 'financial_receipt_reversal',
                        'financial_receipt_id' => $locked->id,
                        'reversal_of_treasury_transaction_id' => $meta['treasury_transaction_id'],
                    ],
                    executedByEmployeeId: $locked->collector_employee_id,
                    counterparty: $locked->payer_name,
                    category: $connector->handoverCategory(),
                );

                $meta['reversal_treasury_transaction_id'] = $reversalTx->id;
            }

            $locked->update([
                'status' => FinancialReceiptStatus::REVERSED->value,
                'metadata' => $meta,
            ]);

            $wasReversed = true;

            return $locked;
        });

        if ($wasReversed) {
            $connector->onReceiptReversed($source, $receipt, $reason, $user);
        }

        return $receipt->refresh();
    }

    /**
     * Receipts eligible for a cash handover (confirmed cash with a remainder).
     *
     * @return Collection<int, FinancialReceipt>
     */
    public static function availableForHandover(ServiceTransaction $transaction, ?int $collectorEmployeeId = null)
    {
        return static::availableForSource(
            FinancialReceipt::SOURCE_SERVICE_TRANSACTION,
            (int) $transaction->id,
            $collectorEmployeeId ?? $transaction->executed_by,
        );
    }

    /**
     * Receipts eligible for a cash handover for any source.
     *
     * @return Collection<int, FinancialReceipt>
     */
    public static function availableForSource(string $sourceType, int $sourceId, ?int $collectorEmployeeId = null): Collection
    {
        $receipts = FinancialReceipt::forSource($sourceType, $sourceId)
            ->confirmed()
            ->cash()
            ->when($collectorEmployeeId, fn ($query) => $query->where('collector_employee_id', $collectorEmployeeId))
            ->orderBy('id')
            ->get();

        return $receipts
            ->filter(fn (FinancialReceipt $receipt) => $receipt->availableForHandover() > 0)
            ->values();
    }

    /**
     * Convert a deferred handover intent into a real handover request exactly
     * once, under the original requester's permissions and scope. Confirmation
     * itself never fails because of the handover; the outcome is stored on the
     * receipt metadata for the UI to surface.
     */
    private static function fulfillPendingHandoverIntent(FinancialReceipt $receipt): void
    {
        $meta = $receipt->metadata ?? [];
        $intent = $meta['handover_intent'] ?? null;

        if (! is_array($intent) || ! empty($meta['handover_intent_handled_at']) || ! $receipt->isCash()) {
            return;
        }

        $meta['handover_intent_handled_at'] = now()->toDateTimeString();

        try {
            $requester = User::find($intent['requested_by'] ?? null);

            if (! $requester) {
                throw new AuthorizationException(__('financial.errors.missing_permission'));
            }

            $handover = CashHandoverService::request(
                [(int) $receipt->id => round((float) $receipt->amount, 2)],
                [
                    'request_key' => (string) ($intent['request_key'] ?? CashHandoverService::newRequestKey()),
                    'notes' => __('financial.descriptions.handover_intent_note', ['number' => $receipt->receipt_number]),
                ],
                $requester,
            );

            $meta['handover_intent_status'] = 'fulfilled';
            $meta['handover_id'] = $handover->id;
        } catch (AuthorizationException|ValidationException $e) {
            $meta['handover_intent_status'] = 'failed';
            $meta['handover_intent_error'] = $e instanceof ValidationException
                ? ($e->validator?->errors()->first() ?? $e->getMessage())
                : $e->getMessage();
        }

        $receipt->update(['metadata' => $meta]);
    }

    public static function sourceTransaction(FinancialReceipt $receipt): ServiceTransaction
    {
        if ($receipt->source_type !== FinancialReceipt::SOURCE_SERVICE_TRANSACTION) {
            throw ValidationException::withMessages([
                'source_type' => __('financial.errors.unsupported_source'),
            ]);
        }

        return ServiceTransaction::findOrFail($receipt->source_id);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function fingerprint(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    public static function newRequestKey(): string
    {
        return 'rcp:'.Str::uuid()->toString();
    }
}
