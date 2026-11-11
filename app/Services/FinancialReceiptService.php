<?php

namespace App\Services;

use App\Enums\FinancialHandoverStatus;
use App\Enums\FinancialPaymentMethod;
use App\Enums\FinancialReceiptStatus;
use App\Models\FinancialReceipt;
use App\Models\ServiceTransaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FinancialReceiptService
{
    /**
     * Register a collection receipt. Money is only counted after confirmation.
     *
     * @param  array<string, mixed>  $data
     */
    public static function record(ServiceTransaction $transaction, array $data, ?User $user = null): FinancialReceipt
    {
        $user = ServiceFinancialWorkflowService::authorize($transaction, 'record_service_receipt', $user);
        ServiceFinancialWorkflowService::assertManaged($transaction);

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

        $collectorId = $data['collector_employee_id'] ?? $transaction->executed_by ?? $user->employee?->id;

        if ($method->isCash() && empty($collectorId)) {
            throw ValidationException::withMessages([
                'collector_employee_id' => __('financial.errors.collector_required'),
            ]);
        }

        if ($method->isCash() && empty($data['payer_name'])) {
            throw ValidationException::withMessages([
                'payer_name' => __('financial.errors.payer_required'),
            ]);
        }

        $fingerprint = static::fingerprint([
            'source' => FinancialReceipt::SOURCE_SERVICE_TRANSACTION.':'.$transaction->id,
            'amount' => $amount,
            'method' => $method->value,
            'collector' => $collectorId,
            'reference' => $data['reference_number'] ?? null,
        ]);

        $receipt = DB::transaction(function () use ($transaction, $data, $user, $amount, $method, $requestKey, $collectorId, $fingerprint) {
            $existing = FinancialReceipt::where('request_key', $requestKey)->lockForUpdate()->first();

            if ($existing) {
                if (($existing->metadata['fingerprint'] ?? null) !== $fingerprint) {
                    throw ValidationException::withMessages([
                        'request_key' => __('financial.errors.request_key_reused'),
                    ]);
                }

                return $existing;
            }

            return FinancialReceipt::create([
                'company_id' => $transaction->company_id,
                'branch_id' => $transaction->employee?->branch_id ?? $transaction->executor?->branch_id,
                'source_type' => FinancialReceipt::SOURCE_SERVICE_TRANSACTION,
                'source_id' => $transaction->id,
                'request_key' => $requestKey,
                'collector_employee_id' => $method->isCash() ? $collectorId : ($collectorId ?: null),
                'payer_name' => $data['payer_name'] ?? $transaction->display_beneficiary,
                'amount' => $amount,
                'payment_method' => $method->value,
                'status' => FinancialReceiptStatus::PENDING_CONFIRMATION->value,
                'reference_number' => $data['reference_number'] ?? null,
                'proof_path' => $data['proof_path'] ?? null,
                'destination_type' => $data['destination_type'] ?? null,
                'destination_reference' => $data['destination_reference'] ?? null,
                'received_at' => $data['received_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
                'metadata' => array_merge($data['metadata'] ?? [], [
                    'fingerprint' => $fingerprint,
                    'confirmed_item_amounts' => [],
                ]),
            ]);
        });

        if ($receipt->wasRecentlyCreated) {
            OperationsAuditService::record(
                companyId: (int) $transaction->company_id,
                transactionId: $transaction->id,
                actionType: 'financial_receipt_recorded',
                oldStatus: $transaction->workflow_status,
                newStatus: $transaction->workflow_status,
                payload: [
                    'receipt_number' => $receipt->receipt_number,
                    'amount' => $amount,
                    'payment_method' => $method->value,
                ],
                userId: $user->id,
            );

            DB::afterCommit(function () use ($transaction, $receipt, $user) {
                OperationsNotificationService::notifyFinancialReceiptRecorded($transaction, $receipt, $user);
            });
        }

        if (! empty($data['confirm'])) {
            $receipt = static::confirm($receipt, [
                'reference_number' => $data['reference_number'] ?? null,
                'proof_path' => $data['proof_path'] ?? null,
                'destination_type' => $data['destination_type'] ?? null,
                'destination_reference' => $data['destination_reference'] ?? null,
            ], $user);
        }

        return $receipt->refresh();
    }

    /**
     * Confirm a receipt after verifying its destination and proof when needed.
     *
     * @param  array<string, mixed>  $data
     */
    public static function confirm(FinancialReceipt $receipt, array $data = [], ?User $user = null): FinancialReceipt
    {
        $transaction = static::sourceTransaction($receipt);
        $user = $user ?? auth()->user();

        if ($user) {
            ServiceFinancialWorkflowService::authorize($transaction, 'confirm_service_receipt', $user);
        } else {
            ServiceFinancialWorkflowService::authorize($transaction, 'confirm_service_receipt');
        }

        $receipt = DB::transaction(function () use ($receipt, $data) {
            $locked = FinancialReceipt::whereKey($receipt->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === FinancialReceiptStatus::CONFIRMED) {
                return $locked;
            }

            if ($locked->status === FinancialReceiptStatus::REVERSED) {
                throw ValidationException::withMessages([
                    'status' => __('financial.errors.receipt_reversed'),
                ]);
            }

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

            $locked->update([
                'status' => FinancialReceiptStatus::CONFIRMED->value,
                'confirmed_by' => auth()->id(),
                'confirmed_at' => now(),
                'reference_number' => $reference,
                'proof_path' => $proof,
                'destination_type' => $destinationType,
                'destination_reference' => $destinationReference,
            ]);

            return $locked;
        });

        ServiceFinancialWorkflowService::sync($transaction);
        OperationsReconciliationService::reconcile($transaction);

        OperationsAuditService::record(
            companyId: (int) $transaction->company_id,
            transactionId: $transaction->id,
            actionType: 'financial_receipt_confirmed',
            oldStatus: $transaction->workflow_status,
            newStatus: $transaction->workflow_status,
            payload: [
                'receipt_number' => $receipt->receipt_number,
                'amount' => (float) $receipt->amount,
                'payment_method' => $receipt->payment_method?->value,
                'cash' => $receipt->isCash(),
            ],
            userId: auth()->id(),
        );

        DB::afterCommit(function () use ($transaction, $receipt) {
            OperationsNotificationService::notifyFinancialReceiptConfirmed($transaction, $receipt);
        });

        return $receipt->refresh();
    }

    public static function reverse(FinancialReceipt $receipt, string $reason, ?User $user = null): FinancialReceipt
    {
        $transaction = static::sourceTransaction($receipt);
        ServiceFinancialWorkflowService::authorize($transaction, 'confirm_service_receipt', $user);

        $receipt = DB::transaction(function () use ($receipt, $reason) {
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
            $meta['reversed_by'] = auth()->id();

            $locked->update([
                'status' => FinancialReceiptStatus::REVERSED->value,
                'metadata' => $meta,
            ]);

            return $locked;
        });

        ServiceFinancialWorkflowService::sync($transaction);
        OperationsReconciliationService::reconcile($transaction);

        OperationsAuditService::record(
            companyId: (int) $transaction->company_id,
            transactionId: $transaction->id,
            actionType: 'financial_receipt_reversed',
            oldStatus: $transaction->workflow_status,
            newStatus: $transaction->workflow_status,
            payload: [
                'receipt_number' => $receipt->receipt_number,
                'amount' => (float) $receipt->amount,
                'reason' => $reason,
            ],
            userId: auth()->id(),
        );

        return $receipt->refresh();
    }

    /**
     * Receipts eligible for a cash handover (confirmed cash with a remainder).
     *
     * @return Collection<int, FinancialReceipt>
     */
    public static function availableForHandover(ServiceTransaction $transaction, ?int $collectorEmployeeId = null)
    {
        $collectorEmployeeId ??= $transaction->executed_by;

        $receipts = FinancialReceipt::forSource(FinancialReceipt::SOURCE_SERVICE_TRANSACTION, (int) $transaction->id)
            ->confirmed()
            ->cash()
            ->when($collectorEmployeeId, fn ($query) => $query->where('collector_employee_id', $collectorEmployeeId))
            ->orderBy('id')
            ->get();

        return $receipts
            ->filter(fn (FinancialReceipt $receipt) => $receipt->availableForHandover() > 0)
            ->values();
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
