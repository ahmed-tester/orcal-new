<?php

namespace App\Services;

use App\Enums\FinancialHandoverStatus;
use App\Enums\FinancialPaymentMethod;
use App\Enums\FinancialReceiptStatus;
use App\Enums\OperationWorkflowStatus;
use App\Models\CashHandover;
use App\Models\FinancialReceipt;
use App\Models\ServiceTransaction;
use App\Models\UnifiedBatchDeposit;
use App\Models\UnifiedBatchDepositItem;
use App\Models\UnifiedSuspenseAmount;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OperationsSettlementService
{
    /**
     * Create a batch bank deposit and allocate amounts to selected transactions.
     *
     * @param  array{
     *     company_id: int,
     *     deposit_date: string,
     *     bank_name: string,
     *     reference_number: string,
     *     receipt_path?: string|null,
     *     notes?: string|null,
     *     items: array<int, array{service_transaction_id: int, amount: float, notes?: string|null}>
     * }  $data
     */
    public static function createBatchDeposit(array $data): UnifiedBatchDeposit
    {
        return DB::transaction(function () use ($data) {
            $companyId = $data['company_id'];
            $itemsData = $data['items'] ?? [];

            $totalAmount = 0.0;
            foreach ($itemsData as $item) {
                $totalAmount += (float) ($item['amount'] ?? 0);
            }

            $deposit = UnifiedBatchDeposit::create([
                'company_id' => $companyId,
                'deposit_date' => $data['deposit_date'] ?? now()->toDateString(),
                'bank_name' => $data['bank_name'],
                'reference_number' => $data['reference_number'],
                'total_amount' => $totalAmount,
                'receipt_path' => $data['receipt_path'] ?? null,
                'created_by' => auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($itemsData as $item) {
                $txId = (int) $item['service_transaction_id'];
                $allocatedAmount = (float) $item['amount'];

                UnifiedBatchDepositItem::create([
                    'batch_deposit_id' => $deposit->id,
                    'service_transaction_id' => $txId,
                    'amount' => $allocatedAmount,
                    'notes' => $item['notes'] ?? null,
                ]);

                $tx = ServiceTransaction::where('id', $txId)->lockForUpdate()->first();
                if ($tx) {
                    $meta = $tx->metadata ?? [];
                    $meta['batch_deposit_id'] = $deposit->id;
                    $meta['batch_deposit_number'] = $deposit->deposit_number;
                    $meta['bank_deposit_receipt'] = $deposit->receipt_path;
                    $meta['bank_deposit_reference'] = $deposit->reference_number;
                    $meta['bank_deposit_amount'] = $allocatedAmount;

                    $tx->metadata = $meta;
                    $tx->save();

                    OperationsAuditService::record(
                        companyId: $tx->company_id ?? $companyId,
                        transactionId: $tx->id,
                        actionType: 'batch_deposited',
                        oldStatus: null,
                        newStatus: $tx->workflow_status,
                        payload: [
                            'batch_deposit_number' => $deposit->deposit_number,
                            'allocated_amount' => $allocatedAmount,
                            'bank_name' => $deposit->bank_name,
                        ],
                    );

                    OperationsReconciliationService::reconcile($tx);
                }
            }

            return $deposit;
        });
    }

    /**
     * Determine if a user is authorized to disburse government fees or self-pay for a transaction.
     * Restricts action to assigned custody holder (executed_by) unless user is authorized management.
     */
    public static function canUserDisburse(ServiceTransaction $transaction, ?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        if (! $user) {
            return false;
        }

        // Super Admin or Owner has complete override
        if (method_exists($user, 'isSuperAdminOrOwner') && $user->isSuperAdminOrOwner()) {
            return true;
        }

        // Only Operations Manager / Supervisor with escalation authority
        if ($user->can('resolve_service_escalation')) {
            return true;
        }

        // Strictly the assigned custody holder (executor)
        $employeeId = $user->employee?->id;
        if ($employeeId && (int) $transaction->executed_by === (int) $employeeId) {
            return true;
        }

        return false;
    }

    /**
     * Record government platform disbursement with official receipt and channel.
     */
    public static function recordGovernmentDisbursement(
        ServiceTransaction $transaction,
        float $amount,
        string $channel,
        ?string $receiptPath = null,
        bool $isSelfPaid = false,
        ?string $notes = null,
    ): ServiceTransaction {
        return DB::transaction(function () use ($transaction, $amount, $channel, $receiptPath, $isSelfPaid, $notes) {
            $tx = ServiceTransaction::where('id', $transaction->id)->lockForUpdate()->firstOrFail();

            if (! static::canUserDisburse($tx, auth()->user())) {
                throw ValidationException::withMessages([
                    'amount' => __('services_tracking.disbursement.custody_holder_restriction'),
                ]);
            }

            if (empty($tx->executed_by) && auth()->user()?->employee?->id) {
                $tx->executed_by = auth()->user()->employee->id;
            }

            $oldStatus = $tx->workflow_status;

            $meta = $tx->metadata ?? [];
            $meta['gov_fee_amount'] = $amount;
            $meta['gov_payment_channel'] = $channel;
            $meta['gov_disbursement_notes'] = $notes;
            $meta['gov_payment_receipt'] = $receiptPath ?? ($meta['gov_payment_receipt'] ?? null);

            if ($isSelfPaid) {
                $meta['self_paid'] = true;
                $meta['self_paid_amount'] = $amount;
                $meta['self_paid_channel'] = $channel;
                $meta['self_paid_receipt'] = $receiptPath;
                $meta['self_paid_status'] = 'pending_approval';
                $meta['approval_requested_at'] = now()->toDateTimeString();
                $meta['approval_requested_by'] = auth()->id();
            }

            $tx->metadata = $meta;
            $tx->save();

            OperationsAuditService::record(
                companyId: $tx->company_id ?? 1,
                transactionId: $tx->id,
                actionType: 'disbursement_recorded',
                oldStatus: $oldStatus,
                newStatus: $tx->workflow_status,
                payload: [
                    'amount' => $amount,
                    'channel' => $channel,
                    'is_self_paid' => $isSelfPaid,
                ],
            );

            OperationsReconciliationService::reconcile($tx);

            return $tx;
        });
    }

    /**
     * Approve employee out-of-pocket (self-paid) disbursement and acknowledge employee credit.
     */
    public static function approveSelfPaidDisbursement(
        ServiceTransaction $transaction,
        ?string $approvalNote = null,
        ?int $approvedBy = null,
    ): ServiceTransaction {
        return DB::transaction(function () use ($transaction, $approvalNote, $approvedBy) {
            $tx = ServiceTransaction::where('id', $transaction->id)->lockForUpdate()->firstOrFail();

            $user = auth()->user();
            if ($user && ! (method_exists($user, 'isSuperAdminOrOwner') && $user->isSuperAdminOrOwner())) {
                $requesterId = (int) ($tx->metadata['approval_requested_by'] ?? 0);
                if ($requesterId && $requesterId === $user->id) {
                    throw ValidationException::withMessages([
                        'approval_note' => __('services_tracking.disbursement.cannot_self_approve'),
                    ]);
                }
            }

            $oldStatus = $tx->workflow_status;

            $meta = $tx->metadata ?? [];
            $meta['self_paid_status'] = 'approved';
            $meta['self_paid_approved'] = true;
            $meta['self_paid_approved_at'] = now()->toDateTimeString();
            $meta['self_paid_approved_by'] = $approvedBy ?? auth()->id();
            $meta['self_paid_approval_note'] = $approvalNote;

            unset($meta['approval_requested_at']);

            $tx->metadata = $meta;
            $tx->save();

            OperationsAuditService::record(
                companyId: $tx->company_id ?? 1,
                transactionId: $tx->id,
                actionType: 'self_paid_approved',
                oldStatus: $oldStatus,
                newStatus: $tx->workflow_status,
                payload: [
                    'approved_amount' => (float) ($meta['self_paid_amount'] ?? 0),
                    'approval_note' => $approvalNote,
                ],
                userId: $approvedBy ?? auth()->id(),
            );

            OperationsReconciliationService::reconcile($tx);

            return $tx;
        });
    }

    /**
     * Record a new unidentified / suspense amount in the registry.
     *
     * @param  array{
     *     company_id: int,
     *     amount: float,
     *     received_date?: string,
     *     bank_name?: string|null,
     *     reference_number?: string|null,
     *     depositor_name?: string|null,
     *     receipt_path?: string|null,
     *     notes?: string|null
     * }  $data
     */
    public static function recordSuspenseAmount(array $data): UnifiedSuspenseAmount
    {
        return UnifiedSuspenseAmount::create([
            'company_id' => $data['company_id'],
            'amount' => (float) $data['amount'],
            'received_date' => $data['received_date'] ?? now()->toDateString(),
            'bank_name' => $data['bank_name'] ?? null,
            'reference_number' => $data['reference_number'] ?? null,
            'depositor_name' => $data['depositor_name'] ?? null,
            'receipt_path' => $data['receipt_path'] ?? null,
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Suggest open transactions that match the suspense amount in the same company.
     *
     * @return Collection<int, ServiceTransaction>
     */
    public static function suggestMatchingTransactions(UnifiedSuspenseAmount $suspense): Collection
    {
        $targetAmount = (float) $suspense->amount;

        return ServiceTransaction::query()
            ->where('company_id', $suspense->company_id)
            ->where(function ($query) use ($targetAmount) {
                $query->where('remaining_amount', $targetAmount)
                    ->orWhere('total_amount', $targetAmount);
            })
            ->whereNotIn('workflow_status', ['completed', 'at_treasury'])
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Allocate a suspense amount to a designated transaction and resolve the suspense state.
     */
    public static function allocateSuspenseAmount(
        UnifiedSuspenseAmount $suspense,
        ServiceTransaction $transaction,
        ?string $note = null,
        ?int $userId = null,
    ): void {
        DB::transaction(function () use ($suspense, $transaction, $note, $userId) {
            $suspenseRecord = UnifiedSuspenseAmount::where('id', $suspense->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($suspenseRecord->status !== 'pending') {
                throw new \RuntimeException('Suspense amount is already allocated or closed.');
            }

            $tx = ServiceTransaction::where('id', $transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            $operatorId = $userId ?? auth()->id();

            if ((int) $suspenseRecord->company_id !== (int) $tx->company_id) {
                throw ValidationException::withMessages(['company_id' => __('financial.errors.out_of_company_scope')]);
            }

            $handover = CashHandover::where('company_id', $suspenseRecord->company_id)
                ->where('status', FinancialHandoverStatus::CONFIRMED->value)
                ->where('metadata->suspense_amount_id', $suspenseRecord->id)
                ->first();
            $managed = ServiceFinancialWorkflowService::isManaged($tx);

            if ($handover) {
                ServiceFinancialWorkflowService::assertManaged($tx);
                $operator = $operatorId ? User::findOrFail($operatorId) : null;
                $operator = ServiceFinancialWorkflowService::authorize($tx, 'manage_suspense_amounts', $operator);
                CashHandoverService::authorizeHandover($handover, 'manage_suspense_amounts', $operator);

                if ((float) $suspenseRecord->amount > FinancialSummaryService::for($tx)['client_remaining']) {
                    throw ValidationException::withMessages([
                        'suspense_amount_id' => __('financial.errors.surplus_exceeds_client_due'),
                    ]);
                }
            }

            $suspenseRecord->update([
                'status' => 'allocated',
                'allocated_service_transaction_id' => $tx->id,
                'allocated_at' => now(),
                'allocated_by' => $operatorId,
                'approved_by' => $operatorId,
                'notes' => trim(($suspenseRecord->notes ? $suspenseRecord->notes.' | ' : '').($note ?? '')),
            ]);

            if (! $managed) {
                $tx->paid_amount = (float) $tx->paid_amount + (float) $suspenseRecord->amount;
            }

            $meta = $tx->metadata ?? [];
            $meta['suspense_amount_id'] = $suspenseRecord->id;
            $meta['suspense_number'] = $suspenseRecord->suspense_number;
            $meta['suspense_reference'] = $suspenseRecord->reference_number;
            $meta['suspense_allocated_at'] = now()->toDateTimeString();

            if (! $handover && empty($meta['bank_deposit_reference']) && ! empty($suspenseRecord->reference_number)) {
                $meta['bank_deposit_reference'] = $suspenseRecord->reference_number;
            }
            if (! $handover && empty($meta['bank_deposit_receipt']) && ! empty($suspenseRecord->receipt_path)) {
                $meta['bank_deposit_receipt'] = $suspenseRecord->receipt_path;
            }

            $tx->metadata = $meta;
            $tx->save();

            if ($managed) {
                FinancialReceipt::create([
                    'company_id' => $tx->company_id,
                    'branch_id' => $tx->employee?->branch_id ?? $tx->executor?->branch_id,
                    'source_type' => FinancialReceipt::SOURCE_SERVICE_TRANSACTION,
                    'source_id' => $tx->id,
                    'request_key' => FinancialReceiptService::newRequestKey(),
                    'collector_employee_id' => $handover ? $handover->collector_employee_id : $tx->executed_by,
                    'payer_name' => $suspenseRecord->depositor_name ?? $tx->display_beneficiary,
                    'amount' => (float) $suspenseRecord->amount,
                    'payment_method' => $handover ? FinancialPaymentMethod::CASH->value : FinancialPaymentMethod::BANK_TRANSFER->value,
                    'status' => FinancialReceiptStatus::CONFIRMED->value,
                    'reference_number' => $suspenseRecord->reference_number ?? ('SUSP-'.$suspenseRecord->id),
                    'proof_path' => $suspenseRecord->receipt_path,
                    'destination_type' => $handover ? null : 'company_account',
                    'destination_reference' => $handover?->handover_number ?? $suspenseRecord->bank_name ?? $suspenseRecord->suspense_number,
                    'confirmed_by' => $operatorId,
                    'confirmed_at' => now(),
                    'received_at' => $suspenseRecord->received_date ? \Carbon\Carbon::parse($suspenseRecord->received_date) : now(),
                    'notes' => __('financial.descriptions.extra_cash_note', ['number' => $suspenseRecord->reference_number ?? (string) $suspenseRecord->id]),
                    'created_by' => $operatorId,
                    'metadata' => [
                        'suspense_amount_id' => $suspenseRecord->id,
                        'allocated_from_suspense' => true,
                        'cash_handover_id' => $handover?->id,
                        'treasury_transaction_id' => $handover?->treasury_transaction_id,
                    ],
                ]);

                ServiceFinancialWorkflowService::sync($tx);
                OperationsReconciliationService::reconcile($tx);
            }
        });
    }

    public static function reverseCashSurplusAllocation(ServiceTransaction $transaction, FinancialReceipt $receipt, string $reason): void
    {
        $operator = ServiceFinancialWorkflowService::authorize($transaction, 'manage_suspense_amounts');

        if (blank($reason) || ! $receipt->isAllocatedTreasuryCash()) {
            throw ValidationException::withMessages(['reason' => __('financial.errors.reversal_reason_required')]);
        }

        DB::transaction(function () use ($transaction, $receipt, $reason, $operator) {
            $suspense = UnifiedSuspenseAmount::whereKey($receipt->metadata['suspense_amount_id'])->lockForUpdate()->firstOrFail();
            $tx = ServiceTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            $locked = $tx->financialReceipts()->whereKey($receipt->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === FinancialReceiptStatus::REVERSED) {
                return;
            }

            if ($suspense->status !== 'allocated' || (int) $suspense->allocated_service_transaction_id !== $tx->id) {
                throw ValidationException::withMessages(['status' => __('financial.errors.surplus_allocation_changed')]);
            }

            $handover = CashHandover::findOrFail($locked->metadata['cash_handover_id']);
            CashHandoverService::authorizeHandover($handover, 'manage_suspense_amounts', $operator);
            $summary = FinancialSummaryService::for($tx);

            if ($summary['treasury_paid'] > $summary['treasury_received'] - (float) $locked->amount) {
                throw ValidationException::withMessages(['status' => __('financial.errors.surplus_already_spent')]);
            }

            $locked->update([
                'status' => FinancialReceiptStatus::REVERSED->value,
                'metadata' => array_merge($locked->metadata, [
                    'reversal_reason' => $reason,
                    'reversed_by' => $operator->id,
                    'reversed_at' => now()->toDateTimeString(),
                ]),
            ]);

            $suspense->update([
                'status' => 'pending',
                'allocated_service_transaction_id' => null,
                'allocated_at' => null,
                'allocated_by' => null,
                'approved_by' => null,
            ]);

            ServiceFinancialWorkflowService::sync($tx);
            OperationsReconciliationService::reconcile($tx);
        });
    }

    /**
     * Escalate an operational impediment on a transaction to management.
     *
     * @param  array{reason: string, notes: string, attachment?: string|null}  $data
     */
    public static function escalateToManagement(
        ServiceTransaction $transaction,
        array $data,
        ?int $userId = null,
    ): ServiceTransaction {
        return DB::transaction(function () use ($transaction, $data, $userId) {
            $tx = ServiceTransaction::where('id', $transaction->id)->lockForUpdate()->firstOrFail();

            $operatorId = $userId ?? auth()->id();
            $oldStatus = $tx->workflow_status;

            $meta = $tx->metadata ?? [];
            $meta['is_escalated'] = true;
            $meta['escalation_reason'] = $data['reason'];
            $meta['escalation_notes'] = $data['notes'];
            $meta['escalation_attachment'] = $data['attachment'] ?? null;
            $meta['escalated_at'] = now()->toDateTimeString();
            $meta['escalated_by'] = $operatorId;

            $tx->metadata = $meta;
            $tx->workflow_status = OperationWorkflowStatus::ESCALATED->value;
            $tx->save();

            OperationsAuditService::record(
                companyId: $tx->company_id ?? 1,
                transactionId: $tx->id,
                actionType: 'escalated',
                oldStatus: $oldStatus,
                newStatus: OperationWorkflowStatus::ESCALATED->value,
                payload: [
                    'reason' => $data['reason'],
                    'notes' => $data['notes'],
                    'attachment' => $data['attachment'] ?? null,
                ],
                userId: $operatorId,
            );

            OperationsNotificationService::notifyEscalated($tx, $data);

            return $tx;
        });
    }

    /**
     * Resolve management escalation and return the transaction to the normal workflow.
     */
    public static function resolveEscalation(
        ServiceTransaction $transaction,
        ?string $resolutionNotes = null,
        ?int $userId = null,
    ): ServiceTransaction {
        return DB::transaction(function () use ($transaction, $resolutionNotes, $userId) {
            $tx = ServiceTransaction::where('id', $transaction->id)->lockForUpdate()->firstOrFail();

            $operatorId = $userId ?? auth()->id();
            $oldStatus = $tx->workflow_status;

            $meta = $tx->metadata ?? [];
            $meta['is_escalated'] = false;
            $meta['escalation_resolved_at'] = now()->toDateTimeString();
            $meta['escalation_resolved_by'] = $operatorId;
            $meta['escalation_resolution_notes'] = $resolutionNotes;

            $tx->metadata = $meta;
            $tx->workflow_status = $tx->resolveWorkflowStatus();
            $tx->save();

            OperationsAuditService::record(
                companyId: $tx->company_id ?? 1,
                transactionId: $tx->id,
                actionType: 'escalation_resolved',
                oldStatus: $oldStatus,
                newStatus: $tx->workflow_status,
                payload: [
                    'resolution_notes' => $resolutionNotes,
                ],
                userId: $operatorId,
            );

            OperationsNotificationService::notifyEscalationResolved($tx, $resolutionNotes);

            OperationsReconciliationService::reconcile($tx);

            return $tx;
        });
    }

    /**
     * Attach government and bank receipts to a transaction and trigger reconciliation.
     *
     * @param  array{gov_payment_receipt?: string|null, bank_deposit_receipt?: string|null}  $data
     */
    public static function attachProofs(ServiceTransaction $transaction, array $data): ServiceTransaction
    {
        $meta = $transaction->metadata ?? [];
        if (! empty($data['gov_payment_receipt'])) {
            $meta['gov_payment_receipt'] = $data['gov_payment_receipt'];
        }
        if (! empty($data['bank_deposit_receipt'])) {
            $meta['bank_deposit_receipt'] = $data['bank_deposit_receipt'];
        }

        $transaction->metadata = $meta;
        $transaction->save();

        OperationsAuditService::record(
            companyId: $transaction->company_id ?? 1,
            transactionId: $transaction->id,
            actionType: 'proofs_attached',
            oldStatus: $transaction->workflow_status,
            newStatus: $transaction->workflow_status,
            payload: [
                'has_gov_receipt' => ! empty($meta['gov_payment_receipt']),
                'has_bank_receipt' => ! empty($meta['bank_deposit_receipt']),
            ],
        );

        return OperationsReconciliationService::reconcile($transaction);
    }

    /**
     * Create batch bank deposit for multiple selected transactions.
     *
     * @param  \Illuminate\Support\Collection<int, ServiceTransaction>  $transactions
     * @param  array<string, mixed>  $data
     */
    public static function processBatchDeposit(Collection $transactions, array $data): UnifiedBatchDeposit
    {
        $first = $transactions->first();
        $companyId = $first?->company_id ?? auth()->user()?->company_id ?? 1;

        $items = [];
        foreach ($transactions as $tx) {
            $paidAmount = (float) $tx->paid_amount;
            if ($paidAmount <= 0) {
                $paidAmount = (float) ($tx->metadata['gov_fee_amount'] ?? $tx->total_amount);
            }

            $items[] = [
                'service_transaction_id' => $tx->id,
                'amount' => $paidAmount,
                'notes' => __('services_tracking.batch_deposit.item_note', ['tx' => $tx->transaction_number]),
            ];
        }

        return static::createBatchDeposit([
            'company_id' => $companyId,
            'deposit_date' => $data['deposit_date'],
            'bank_name' => $data['bank_name'],
            'reference_number' => $data['reference_number'],
            'receipt_path' => $data['receipt_path'] ?? null,
            'notes' => $data['notes'] ?? null,
            'items' => $items,
        ]);
    }
}
