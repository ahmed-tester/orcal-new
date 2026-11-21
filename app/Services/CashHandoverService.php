<?php

namespace App\Services;

use App\Enums\FinancialCashDirection;
use App\Enums\FinancialHandoverStatus;
use App\Enums\FinancialReceiptStatus;
use App\Models\CashHandover;
use App\Models\CashHandoverItem;
use App\Models\FinancialReceipt;
use App\Models\ServiceTransaction;
use App\Models\TreasuryTransaction;
use App\Models\UnifiedSuspenseAmount;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CashHandoverService
{
    public static function authorizeHandover(CashHandover $handover, string $permission, ?User $user = null): User
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            throw new AuthorizationException(__('financial.errors.unauthenticated'));
        }

        if ($user->isSuperAdmin()) {
            return $user;
        }

        ServiceFinancialWorkflowService::authorizeCompany($user, (int) $handover->company_id, $permission);

        if ($handover->branch_id && $user->branch_id && ! $user->canAccessAllBranches()
            && (int) $user->branch_id !== (int) $handover->branch_id) {
            throw new AuthorizationException(__('financial.errors.out_of_branch_scope'));
        }

        return $user;
    }

    /**
     * Request a cash handover for confirmed cash receipts of the same collector.
     *
     * @param  array<int|string, float>  $receiptAmounts  receipt_id => requested amount
     * @param  array<string, mixed>  $options
     */
    public static function request(array $receiptAmounts, array $options = [], ?User $user = null): CashHandover
    {
        $user = $user ?? auth()->user();

        $requestKey = trim((string) ($options['request_key'] ?? ''));
        if ($requestKey === '') {
            throw ValidationException::withMessages([
                'request_key' => __('financial.errors.request_key_required'),
            ]);
        }

        $receiptIds = array_values(array_unique(array_map('intval', array_keys($receiptAmounts))));

        if ($receiptIds === []) {
            throw ValidationException::withMessages([
                'items' => __('financial.errors.handover_items_required'),
            ]);
        }

        $fingerprint = hash('sha256', json_encode([
            'receipts' => array_map(fn ($id) => [$id, round((float) $receiptAmounts[$id], 2)], $receiptIds),
        ], JSON_THROW_ON_ERROR));

        $handover = DB::transaction(function () use ($receiptIds, $receiptAmounts, $options, $requestKey, $fingerprint, $user) {
            $existing = CashHandover::where('request_key', $requestKey)->lockForUpdate()->first();

            if ($existing) {
                if (($existing->metadata['request_fingerprint'] ?? null) !== $fingerprint) {
                    throw ValidationException::withMessages([
                        'request_key' => __('financial.errors.request_key_reused'),
                    ]);
                }

                return $existing;
            }

            $receipts = FinancialReceipt::whereIn('id', $receiptIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($receipts->count() !== count($receiptIds)) {
                throw ValidationException::withMessages([
                    'items' => __('financial.errors.receipt_not_found'),
                ]);
            }

            $companyId = (int) $receipts->first()->company_id;
            $collectorId = (int) $receipts->first()->collector_employee_id;
            $branchId = $receipts->first()->branch_id;

            if ($user) {
                ServiceFinancialWorkflowService::authorizeCompany($user, $companyId, 'request_cash_handover');

                if ($branchId && $user->branch_id && ! $user->canAccessAllBranches()
                    && (int) $user->branch_id !== (int) $branchId) {
                    throw new AuthorizationException(__('financial.errors.out_of_branch_scope'));
                }
            }

            $totalRequested = 0.0;

            foreach ($receipts as $receipt) {
                $amount = round((float) ($receiptAmounts[$receipt->id] ?? 0), 2);

                if ((int) $receipt->company_id !== $companyId
                    || (int) $receipt->collector_employee_id !== $collectorId
                    || ! $receipt->isCash()
                    || $receipt->status !== FinancialReceiptStatus::CONFIRMED) {
                    throw ValidationException::withMessages([
                        'items' => __('financial.errors.handover_receipt_mismatch'),
                    ]);
                }

                if ($amount <= 0) {
                    throw ValidationException::withMessages([
                        'items' => __('financial.errors.invalid_amount'),
                    ]);
                }

                $available = $receipt->availableForHandover();

                if ($amount > $available + 0.009) {
                    throw ValidationException::withMessages([
                        'items' => __('financial.errors.handover_exceeds_custody', [
                            'amount' => number_format($available, 2),
                        ]),
                    ]);
                }

                $totalRequested += $amount;
            }

            $handover = CashHandover::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'request_key' => $requestKey,
                'collector_employee_id' => $collectorId,
                'delivered_by_employee_id' => $options['delivered_by_employee_id'] ?? $collectorId,
                'requested_by' => $user?->id,
                'total_requested_amount' => round($totalRequested, 2),
                'status' => FinancialHandoverStatus::SUBMITTED->value,
                'notes' => $options['notes'] ?? null,
                'metadata' => [
                    'request_fingerprint' => $fingerprint,
                    'submitted_by' => $user?->id,
                    'submitted_at' => now()->toDateTimeString(),
                ],
            ]);

            foreach ($receipts as $receipt) {
                CashHandoverItem::create([
                    'cash_handover_id' => $handover->id,
                    'financial_receipt_id' => $receipt->id,
                    'company_id' => $companyId,
                    'requested_amount' => round((float) $receiptAmounts[$receipt->id], 2),
                    'confirmed_amount' => 0,
                ]);
            }

            return $handover->load('items');
        });

        if ($handover->wasRecentlyCreated) {
            DB::afterCommit(function () use ($handover) {
                OperationsNotificationService::notifyCashHandoverRequested($handover);
            });
        }

        return $handover;
    }

    public static function requestForTransaction(ServiceTransaction $transaction, array $items, array $options = [], ?User $user = null): CashHandover
    {
        ServiceFinancialWorkflowService::assertManaged($transaction);
        ServiceFinancialWorkflowService::authorize($transaction, 'request_cash_handover', $user);

        return static::request($items, $options, $user);
    }

    /**
     * Confirm the received cash, distribute it per handover item and create
     * exactly one incoming treasury movement.
     *
     * @param  array<string, mixed>  $data
     */
    public static function confirm(CashHandover $handover, array $data = [], ?User $user = null): CashHandover
    {
        $user = static::authorizeHandover($handover, 'confirm_cash_handover', $user);

        $confirmationKey = trim((string) ($data['confirmation_key'] ?? ''));
        if ($confirmationKey === '') {
            throw ValidationException::withMessages([
                'confirmation_key' => __('financial.errors.confirmation_key_required'),
            ]);
        }

        $handover = DB::transaction(function () use ($handover, $data, $user, $confirmationKey) {
            $locked = CashHandover::whereKey($handover->id)->lockForUpdate()->firstOrFail();

            $normalizedItems = collect($data['confirmed_amounts'] ?? [])
                ->mapWithKeys(fn ($amount, $itemId) => [(string) $itemId => round((float) $amount, 2)])
                ->sortKeys()
                ->all();

            $fingerprint = hash('sha256', json_encode([
                'handover' => $locked->id,
                'items' => $normalizedItems,
                'extra' => round((float) ($data['extra_amount'] ?? 0), 2),
            ], JSON_THROW_ON_ERROR));

            if ($locked->status === FinancialHandoverStatus::CONFIRMED) {
                $stored = $locked->metadata['confirmation_items'] ?? null;

                if ($locked->confirmation_key === $confirmationKey && $stored == $normalizedItems) {
                    return $locked;
                }

                throw ValidationException::withMessages([
                    'confirmation_key' => __('financial.errors.handover_already_confirmed'),
                ]);
            }

            if ($locked->status !== FinancialHandoverStatus::SUBMITTED) {
                throw ValidationException::withMessages([
                    'status' => __('financial.errors.handover_not_submitted'),
                ]);
            }

            $signature = strtr(trim((string) ($data['signature_code'] ?? '')), [
                '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
                '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            ]);

            if (! hash_equals(static::signatureCode($locked), $signature)) {
                throw ValidationException::withMessages([
                    'signature_code' => __('financial.errors.invalid_handover_signature'),
                ]);
            }

            $items = $locked->items()->orderBy('id')->lockForUpdate()->with('receipt')->get();

            $receipts = FinancialReceipt::whereIn('id', $items->pluck('financial_receipt_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $confirmedAmounts = (array) ($data['confirmed_amounts'] ?? []);
            $confirmedItemsTotal = 0.0;

            foreach ($items as $item) {
                $confirmed = $confirmedAmounts[$item->id] ?? $confirmedAmounts[$item->financial_receipt_id] ?? $item->requested_amount;
                $confirmed = round((float) $confirmed, 2);

                if ($confirmed < 0 || $confirmed > (float) $item->requested_amount + 0.009) {
                    throw ValidationException::withMessages([
                        'confirmed_amounts' => __('financial.errors.handover_item_exceeds_requested'),
                    ]);
                }

                $receipt = $receipts->get($item->financial_receipt_id);

                if (! $receipt || $receipt->status !== FinancialReceiptStatus::CONFIRMED) {
                    throw ValidationException::withMessages([
                        'items' => __('financial.errors.handover_receipt_mismatch'),
                    ]);
                }

                $item->confirmed_amount = $confirmed;
                $item->confirmed_at = now();
                $item->save();

                $confirmedItemsTotal += $confirmed;
            }

            $confirmedItemsTotal = round($confirmedItemsTotal, 2);
            $extra = round((float) ($data['extra_amount'] ?? 0), 2);

            if ($extra < 0) {
                throw ValidationException::withMessages([
                    'extra_amount' => __('financial.errors.invalid_amount'),
                ]);
            }

            if ($extra > 0 && $confirmedItemsTotal + 0.009 < (float) $locked->total_requested_amount) {
                throw ValidationException::withMessages([
                    'extra_amount' => __('financial.errors.extra_requires_full_attribution'),
                ]);
            }

            if ($extra > 0 && empty($data['extra_note'])) {
                throw ValidationException::withMessages([
                    'extra_note' => __('financial.errors.extra_note_required'),
                ]);
            }

            $treasuryAmount = round($confirmedItemsTotal + $extra, 2);

            if ($treasuryAmount <= 0) {
                throw ValidationException::withMessages([
                    'amount' => __('financial.errors.invalid_amount'),
                ]);
            }

            $treasury = FinancialTreasuryService::post(
                companyId: (int) $locked->company_id,
                direction: FinancialCashDirection::INCOMING,
                amount: $treasuryAmount,
                description: __('financial.descriptions.handover_confirmed', ['number' => $locked->handover_number]),
                metadata: [
                    'source' => 'cash_handover',
                    'cash_handover_id' => $locked->id,
                    'handover_number' => $locked->handover_number,
                ],
                executedByEmployeeId: $locked->delivered_by_employee_id,
                counterparty: $locked->collector?->user?->name,
            );

            $meta = $locked->metadata ?? [];
            $meta['confirmed_item_amounts'] = $items->mapWithKeys(
                fn (CashHandoverItem $item) => [$item->financial_receipt_id => (float) $item->confirmed_amount]
            )->all();
            $meta['extra_amount'] = $extra;
            $meta['extra_note'] = $data['extra_note'] ?? null;
            $meta['confirmation_fingerprint'] = $fingerprint;
            $meta['confirmation_items'] = $normalizedItems;
            $meta['received_by'] = $user?->id ?? auth()->id();
            $meta['treasury_amount'] = $treasuryAmount;

            $locked->update([
                'status' => FinancialHandoverStatus::CONFIRMED->value,
                'confirmation_key' => $confirmationKey,
                'total_confirmed_amount' => $confirmedItemsTotal,
                'difference_amount' => round($treasuryAmount - (float) $locked->total_requested_amount, 2),
                'treasury_transaction_id' => $treasury->id,
                'received_by' => $user?->id ?? auth()->id(),
                'confirmed_at' => now(),
                'metadata' => $meta,
            ]);

            if ($extra > 0) {
                $suspense = OperationsSettlementService::recordSuspenseAmount([
                    'company_id' => (int) $locked->company_id,
                    'amount' => $extra,
                    'reference_number' => $locked->handover_number,
                    'depositor_name' => $locked->collector?->user?->name,
                    'notes' => $data['extra_note'] ?? __('financial.descriptions.extra_cash_note', [
                        'number' => $locked->handover_number,
                    ]),
                ]);

                if ($suspense) {
                    $meta['suspense_amount_id'] = $suspense->id;
                    $locked->update(['metadata' => $meta]);
                }
            }

            return $locked;
        });

        static::syncAffectedTransactions($handover);
        OperationsNotificationService::notifyCashHandoverConfirmed($handover);
        static::audit($handover, 'financial_cash_handover_confirmed');

        return $handover->refresh();
    }

    public static function reject(CashHandover $handover, string $reason, ?User $user = null): CashHandover
    {
        $user = static::authorizeHandover($handover, 'confirm_cash_handover', $user);

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => __('financial.errors.rejection_reason_required'),
            ]);
        }

        $handover = DB::transaction(function () use ($handover, $reason, $user) {
            $locked = CashHandover::whereKey($handover->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === FinancialHandoverStatus::REJECTED) {
                return $locked;
            }

            if ($locked->status !== FinancialHandoverStatus::SUBMITTED) {
                throw ValidationException::withMessages([
                    'status' => __('financial.errors.handover_not_submitted'),
                ]);
            }

            $meta = $locked->metadata ?? [];
            $meta['rejected_by'] = $user?->id ?? auth()->id();
            $meta['rejected_at'] = now()->toDateTimeString();

            $locked->update([
                'status' => FinancialHandoverStatus::REJECTED->value,
                'rejection_reason' => $reason,
                'metadata' => $meta,
            ]);

            return $locked;
        });

        OperationsNotificationService::notifyCashHandoverRejected($handover, $reason);
        static::audit($handover, 'financial_cash_handover_rejected');

        return $handover->refresh();
    }

    public static function cancel(CashHandover $handover, ?User $user = null, ?string $reason = null): CashHandover
    {
        $user = static::authorizeHandover($handover, 'request_cash_handover', $user);

        if (! $user->isSuperAdmin() && (int) $handover->requested_by !== (int) $user->id) {
            throw new AuthorizationException(__('financial.errors.only_requester_can_cancel'));
        }

        $handover = DB::transaction(function () use ($handover, $user, $reason) {
            $locked = CashHandover::whereKey($handover->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === FinancialHandoverStatus::CANCELLED) {
                return $locked;
            }

            if ($locked->status !== FinancialHandoverStatus::SUBMITTED) {
                throw ValidationException::withMessages([
                    'status' => __('financial.errors.handover_not_submitted'),
                ]);
            }

            $meta = $locked->metadata ?? [];
            $meta['cancelled_by'] = $user?->id ?? auth()->id();
            $meta['cancelled_at'] = now()->toDateTimeString();
            $meta['cancel_reason'] = $reason;

            $locked->update([
                'status' => FinancialHandoverStatus::CANCELLED->value,
                'metadata' => $meta,
            ]);

            return $locked;
        });

        return $handover->refresh();
    }

    /**
     * Approved reversal of a confirmed handover: opposite treasury movement,
     * custody returns to the collector.
     */
    public static function reverse(CashHandover $handover, string $reason, ?User $user = null): CashHandover
    {
        $user = static::authorizeHandover($handover, 'confirm_cash_handover', $user);

        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => __('financial.errors.reversal_reason_required'),
            ]);
        }

        $handover = DB::transaction(function () use ($handover, $reason, $user) {
            $locked = CashHandover::whereKey($handover->id)->lockForUpdate()->firstOrFail();

            if (! empty($locked->metadata['reversed_at'])) {
                return $locked;
            }

            if ($locked->status !== FinancialHandoverStatus::CONFIRMED) {
                throw ValidationException::withMessages([
                    'status' => __('financial.errors.only_confirmed_handover_can_be_reversed'),
                ]);
            }

            $receiptIds = $locked->items()->where('confirmed_amount', '>', 0)->pluck('financial_receipt_id');
            $receipts = FinancialReceipt::whereIn('id', $receiptIds)->orderBy('id')->lockForUpdate()->get();

            if ($receipts->contains(fn (FinancialReceipt $receipt) => $receipt->refundedAmount() > 0)) {
                throw ValidationException::withMessages([
                    'status' => __('financial.errors.cannot_reverse_refunded_handover'),
                ]);
            }

            // Check if extra cash surplus was recorded and verify its status
            $suspenseId = $locked->metadata['suspense_amount_id'] ?? null;
            if ($suspenseId) {
                $suspense = UnifiedSuspenseAmount::whereKey($suspenseId)->lockForUpdate()->first();
                if ($suspense) {
                    if ($suspense->status === 'allocated') {
                        throw ValidationException::withMessages([
                            'status' => __('financial.errors.cannot_reverse_allocated_surplus'),
                        ]);
                    }

                    $suspense->update([
                        'status' => 'cancelled',
                        'notes' => trim(($suspense->notes ? $suspense->notes.' | ' : '').'Cancelled by handover reversal '.$locked->handover_number),
                    ]);
                }
            }

            $amount = round((float) $locked->total_confirmed_amount + (float) ($locked->metadata['extra_amount'] ?? 0), 2);

            $reversal = FinancialTreasuryService::post(
                companyId: (int) $locked->company_id,
                direction: FinancialCashDirection::OUTGOING,
                amount: $amount,
                description: __('financial.descriptions.handover_reversed', ['number' => $locked->handover_number]),
                metadata: [
                    'source' => 'cash_handover_reversal',
                    'cash_handover_id' => $locked->id,
                    'reversal_of_treasury_transaction_id' => $locked->treasury_transaction_id,
                ],
                executedByEmployeeId: $locked->delivered_by_employee_id,
            );

            $meta = $locked->metadata ?? [];
            $meta['reversed_at'] = now()->toDateTimeString();
            $meta['reversed_by'] = $user?->id ?? auth()->id();
            $meta['reversal_reason'] = $reason;
            $meta['reversal_treasury_transaction_id'] = $reversal->id;

            $locked->update([
                'status' => FinancialHandoverStatus::REVERSED->value,
                'metadata' => $meta,
            ]);

            return $locked;
        });

        static::syncAffectedTransactions($handover);
        static::audit($handover, 'financial_cash_handover_reversed');

        return $handover->refresh();
    }

    /**
     * Link an already existing treasury movement to a confirmed handover
     * without creating a new movement.
     */
    public static function linkExisting(CashHandover $handover, TreasuryTransaction $treasury, ?User $user = null): CashHandover
    {
        $user = $user ?? auth()->user();

        if ($user) {
            ServiceFinancialWorkflowService::authorizeCompany($user, (int) $handover->company_id, 'confirm_cash_handover');
        }

        if ($handover->status !== FinancialHandoverStatus::CONFIRMED) {
            throw ValidationException::withMessages([
                'status' => __('financial.errors.link_requires_confirmed_document'),
            ]);
        }

        if (! empty($handover->treasury_transaction_id)) {
            throw ValidationException::withMessages([
                'treasury_transaction_id' => __('financial.errors.posting_already_linked'),
            ]);
        }

        $amount = round((float) $handover->total_confirmed_amount + (float) ($handover->metadata['extra_amount'] ?? 0), 2);

        FinancialTreasuryService::assertLinkable(
            $treasury,
            (int) $handover->company_id,
            FinancialCashDirection::INCOMING,
            $amount,
        );

        $handover->update(['treasury_transaction_id' => $treasury->id]);

        return $handover->refresh();
    }

    public static function newRequestKey(): string
    {
        return 'hnd:'.Str::uuid()->toString();
    }

    /**
     * Split the counted cash into receipt amounts and unattributed surplus.
     *
     * @return array{0: array<int, float>, 1: float}
     */
    public static function splitCountedAmounts(array $countedAmounts, array $requestedAmounts): array
    {
        $confirmedAmounts = [];
        $extra = 0.0;

        foreach (array_intersect_key($countedAmounts, $requestedAmounts) as $itemId => $amount) {
            $counted = round((float) $amount, 2);
            $requested = round((float) $requestedAmounts[$itemId], 2);

            $confirmedAmounts[$itemId] = min($counted, $requested);
            $extra += max(0, $counted - $requested);
        }

        return [$confirmedAmounts, round($extra, 2)];
    }

    /**
     * Parse a handover QR payload ("HANDOVER:<handover_number>") into the
     * handover number. The code identifies the handover only and carries no
     * amounts or other data. Arabic digits are normalized first.
     */
    public static function parseHandoverScanPayload(string $raw): ?string
    {
        $raw = strtr(trim($raw), [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        if (preg_match('/^(?:HANDOVER\s*:\s*)?([A-Za-z0-9\-]+)$/i', $raw, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Find a submitted handover within the user's scope from a scanned code.
     * The confirming user's permission and company/branch scope are re-checked
     * on save by CashHandoverService::confirm().
     */
    public static function findSubmittedByScan(string $raw, ?User $user = null): ?CashHandover
    {
        $number = static::parseHandoverScanPayload($raw);

        if ($number === null) {
            return null;
        }

        return CashHandover::accessibleBy($user)
            ->submitted()
            ->where('handover_number', $number)
            ->first();
    }

    public static function signatureCodeForDelivery(CashHandover $handover, ?User $user = null): ?string
    {
        $user ??= auth()->user();

        if (! $user || ! $handover->isSubmitted()) {
            return null;
        }

        $requesterId = $handover->requested_by ?? $handover->metadata['submitted_by'] ?? null;
        $isRequester = $requesterId && (int) $requesterId === (int) $user->id;
        $isDeliverer = $user->employee?->id
            && (int) $handover->delivered_by_employee_id === (int) $user->employee->id;

        return $isRequester || $isDeliverer ? static::signatureCode($handover) : null;
    }

    private static function signatureCode(CashHandover $handover): string
    {
        $digest = hash_hmac(
            'sha256',
            'cash-handover-signature:'.$handover->id.':'.$handover->request_key,
            app('encrypter')->getKey(),
        );

        return (string) (1000 + (hexdec(substr($digest, 0, 7)) % 9000));
    }

    public static function newConfirmationKey(): string
    {
        return 'hnd-confirm:'.Str::uuid()->toString();
    }

    private static function audit(CashHandover $handover, string $actionType): void
    {
        $transactionIds = CashHandoverItem::where('cash_handover_id', $handover->id)
            ->with('receipt')
            ->get()
            ->map(fn (CashHandoverItem $item) => $item->receipt?->source_id)
            ->filter()
            ->unique();

        foreach ($transactionIds as $transactionId) {
            OperationsAuditService::record(
                companyId: (int) $handover->company_id,
                transactionId: (int) $transactionId,
                actionType: $actionType,
                payload: [
                    'handover_number' => $handover->handover_number,
                    'requested_amount' => (float) $handover->total_requested_amount,
                    'confirmed_amount' => (float) $handover->total_confirmed_amount,
                    'difference_amount' => (float) $handover->difference_amount,
                ],
            );
        }
    }

    private static function syncAffectedTransactions(CashHandover $handover): void
    {
        $transactions = FinancialReceipt::whereIn('id', $handover->items()->pluck('financial_receipt_id'))
            ->get()
            ->map(fn (FinancialReceipt $receipt) => $receipt->source_id)
            ->unique();

        foreach ($transactions as $id) {
            $transaction = ServiceTransaction::find($id);

            if ($transaction) {
                ServiceFinancialWorkflowService::sync($transaction);
                OperationsReconciliationService::reconcile($transaction);
            }
        }
    }
}
