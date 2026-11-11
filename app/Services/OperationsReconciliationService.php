<?php

namespace App\Services;

use App\Enums\OperationWorkflowStatus;
use App\Models\ServiceTransaction;
use App\Models\UnifiedDeficiencyTask;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OperationsReconciliationService
{
    /**
     * Evaluate the bilateral reconciliation of a transaction across 4 pillars:
     * 1. Counterparty & beneficiary presence.
     * 2. Cash custody resolution (must be received in treasury or non-cash).
     * 3. Proofs: Government payment receipt & Bank deposit slip (or batch deposit).
     * 4. Discrepancies & approvals: Remaining unapproved amount or pending self-paid approvals.
     *
     * @return array{
     *     status: string,
     *     missing_parts: array<int, string>,
     *     diff: float,
     *     note: string
     * }
     */
    public static function evaluate(ServiceTransaction $transaction): array
    {
        if (ServiceFinancialWorkflowService::isManaged($transaction)) {
            return static::evaluateManaged($transaction);
        }

        $meta = $transaction->metadata ?? [];
        $missing = [];

        // Pillar 1: Counterparty
        $hasBeneficiary = filled($transaction->display_beneficiary) && $transaction->display_beneficiary !== '—';
        if (! $hasBeneficiary) {
            $missing[] = 'missing_counterparty';
        }

        // Pillar 2: Cash Custody
        $custodyStatus = $meta['custody_status'] ?? 'not_applicable';
        if ($custodyStatus === 'on_employee' && (float) $transaction->paid_amount > 0) {
            $missing[] = 'custody_handover';
        }

        // Pillar 3: Proofs
        $hasGovReceipt = ! empty($meta['gov_payment_receipt']);
        if (! $hasGovReceipt) {
            $missing[] = 'missing_gov_receipt';
        }

        $hasBankDeposit = ! empty($meta['bank_deposit_receipt']) || ! empty($meta['batch_deposit_id']);
        if (! $hasBankDeposit) {
            $missing[] = 'missing_bank_deposit';
        }

        // Pillar 4: Discrepancies & Approvals
        $diff = max(0.0, (float) $transaction->remaining_amount);
        $costBearer = $meta['cost_bearer'] ?? 'client_paid';
        if ($diff > 0 && $costBearer !== 'client_debt') {
            $missing[] = 'amount_mismatch';
        }

        if (($meta['self_paid_status'] ?? '') === 'pending_approval') {
            $missing[] = 'self_paid_approval';
        }

        // Outcome determination
        if (empty($missing)) {
            return [
                'status' => 'matched',
                'missing_parts' => [],
                'diff' => 0.0,
                'note' => 'Matched & Closed 100%',
            ];
        }

        $status = in_array('amount_mismatch', $missing, true)
            ? 'amount_diff'
            : (in_array('missing_gov_receipt', $missing, true) || in_array('bank_deposit', $missing, true)
                ? 'missing_document'
                : (in_array('custody_handover', $missing, true) ? 'missing_custody' : 'missing_counterparty'));

        return [
            'status' => $status,
            'missing_parts' => $missing,
            'diff' => $diff,
            'note' => 'Discrepancies identified: '.implode(', ', $missing),
        ];
    }

    /**
     * Reconciliation of the managed financial path. Numbers come only from
     * FinancialSummaryService (receipts, handover items, disbursements).
     */
    private static function evaluateManaged(ServiceTransaction $transaction): array
    {
        $summary = FinancialSummaryService::for($transaction);
        $missing = [];

        if (! filled($transaction->display_beneficiary) || $transaction->display_beneficiary === '—') {
            $missing[] = 'missing_counterparty';
        }

        if ((float) $summary['custody'] > 0) {
            $missing[] = 'custody_handover';
        }

        if ((int) $summary['pending_receipts_count'] > 0) {
            $missing[] = 'receipt_confirmation';
        }

        if ((float) $summary['client_remaining'] > 0) {
            $missing[] = 'amount_mismatch';
        }

        if (! $summary['fee_defined']) {
            $missing[] = 'fee_rule_missing';
        } elseif ((float) $summary['fee_remaining'] > 0) {
            $missing[] = 'fee_payment_due';
        }

        if ((float) $summary['awaiting_approval'] > 0) {
            $missing[] = 'self_paid_approval';
        }

        if ((float) $summary['employee_due'] > 0) {
            $missing[] = 'employee_reimbursement_due';
        }

        if ((float) $summary['surplus'] > 0) {
            $missing[] = 'surplus_pending';
        }

        if (empty($missing)) {
            return [
                'status' => 'matched',
                'missing_parts' => [],
                'diff' => 0.0,
                'note' => __('services_tracking.reconciliation.managed_matched'),
                'financial' => $summary,
            ];
        }

        $status = match (true) {
            in_array('amount_mismatch', $missing, true) => 'amount_diff',
            in_array('custody_handover', $missing, true) => 'missing_custody',
            in_array('receipt_confirmation', $missing, true) => 'pending_confirmation',
            in_array('fee_payment_due', $missing, true), in_array('fee_rule_missing', $missing, true) => 'missing_document',
            default => 'missing_counterparty',
        };

        $diff = max((float) $summary['client_remaining'], (float) ($summary['fee_remaining'] ?? 0));

        $labels = array_map(
            fn (string $part) => __('services_tracking.reconciliation.tasks.'.$part),
            $missing
        );

        return [
            'status' => $status,
            'missing_parts' => $missing,
            'diff' => round($diff, 2),
            'note' => __('services_tracking.reconciliation.managed_note', ['parts' => implode('، ', $labels)]),
            'financial' => $summary,
        ];
    }

    /**
     * Run full bilateral reconciliation and synchronize deficiency tasks.
     */
    public static function reconcile(ServiceTransaction $transaction): ServiceTransaction
    {
        return DB::transaction(function () use ($transaction) {
            $tx = ServiceTransaction::where('id', $transaction->id)->lockForUpdate()->firstOrFail();
            $eval = static::evaluate($tx);

            $meta = $tx->metadata ?? [];
            $meta['reconciliation_status'] = $eval['status'];
            $meta['reconciliation_missing_parts'] = $eval['missing_parts'];
            $meta['reconciliation_diff'] = $eval['diff'];
            $meta['reconciliation_evaluated_at'] = now()->toDateTimeString();

            if ($eval['status'] === 'matched') {
                unset($meta['mismatch_reason']);
                $tx->service_status = 'completed';
                if (empty($tx->completion_date)) {
                    $tx->completion_date = now()->toDateString();
                }
            } else {
                $meta['mismatch_reason'] = $eval['note'];
            }

            $tx->metadata = $meta;
            $tx->workflow_status = $tx->resolveWorkflowStatus();
            $tx->save();

            static::syncDeficiencyTasks($tx, $eval);

            return $tx;
        });
    }

    /**
     * Synchronize open deficiency tasks for missing components and auto-resolve resolved ones.
     *
     * @param  array{status: string, missing_parts: array<int, string>, diff: float, note: string}|null  $eval
     * @return Collection<int, UnifiedDeficiencyTask>
     */
    public static function syncDeficiencyTasks(ServiceTransaction $transaction, ?array $eval = null): Collection
    {
        $evaluation = $eval ?? static::evaluate($transaction);
        $missingParts = $evaluation['missing_parts'];

        $companyId = $transaction->company_id;
        $responsibleEmployeeId = $transaction->executed_by ?? $transaction->employee_id;

        if (! $companyId) {
            return new Collection;
        }

        $newlyCreatedTypes = [];

        // Create or ensure single unique task for current missing parts
        foreach ($missingParts as $type) {
            $titleKey = 'services_tracking.reconciliation.tasks.'.$type;
            $descKey = 'services_tracking.reconciliation.tasks.'.$type.'_desc';

            $taskKey = "svc-fin:{$transaction->id}:{$type}";

            $existingTask = UnifiedDeficiencyTask::where('task_key', $taskKey)->latest()->first()
                ?? UnifiedDeficiencyTask::where('service_transaction_id', $transaction->id)
                    ->where('deficiency_type', $type)
                    ->latest()
                    ->first();

            if (! $existingTask) {
                $task = UnifiedDeficiencyTask::create([
                    'company_id' => $companyId,
                    'service_transaction_id' => $transaction->id,
                    'task_key' => $taskKey,
                    'deficiency_type' => $type,
                    'status' => 'open',
                    'title' => __($titleKey),
                    'description' => __($descKey),
                    'responsible_employee_id' => $responsibleEmployeeId,
                    'assigned_by_user_id' => auth()->id(),
                    'due_date' => now()->addDays(2)->toDateString(),
                ]);

                $newlyCreatedTypes[] = $type;
            } else {
                $updates = [];

                if (empty($existingTask->task_key)) {
                    $updates['task_key'] = $taskKey;
                }

                if ($existingTask->status !== 'open') {
                    $updates['status'] = 'open';
                    $updates['resolved_at'] = null;
                    $updates['resolved_by_user_id'] = null;
                    $updates['resolution_notes'] = null;
                }

                if ($responsibleEmployeeId && (int) $existingTask->responsible_employee_id !== (int) $responsibleEmployeeId) {
                    $updates['responsible_employee_id'] = $responsibleEmployeeId;
                }

                if (! empty($updates)) {
                    $existingTask->update($updates);
                }
            }
        }

        // Deduplicate any historical duplicate tasks for the same deficiency type on this transaction
        $groupedTasks = UnifiedDeficiencyTask::where('service_transaction_id', $transaction->id)
            ->get()
            ->groupBy('deficiency_type');

        foreach ($groupedTasks as $type => $tasksGroup) {
            if ($tasksGroup->count() > 1) {
                $primaryTask = $tasksGroup->firstWhere('status', 'open') ?? $tasksGroup->sortByDesc('id')->first();
                foreach ($tasksGroup as $otherTask) {
                    if ($otherTask->id !== $primaryTask->id) {
                        $otherTask->delete();
                    }
                }
            }
        }

        // Synchronize all open tasks with the current transaction executor
        if ($responsibleEmployeeId) {
            UnifiedDeficiencyTask::where('service_transaction_id', $transaction->id)
                ->where('status', 'open')
                ->where(function ($q) use ($responsibleEmployeeId) {
                    $q->whereNull('responsible_employee_id')
                        ->orWhere('responsible_employee_id', '!=', $responsibleEmployeeId);
                })
                ->update(['responsible_employee_id' => $responsibleEmployeeId]);
        }

        if (! empty($newlyCreatedTypes)) {
            OperationsNotificationService::notifyDeficiencyTasksCreated($transaction, $newlyCreatedTypes);
        }

        // Auto-resolve existing open tasks whose deficiency has been fulfilled
        $existingOpenTasks = UnifiedDeficiencyTask::where('service_transaction_id', $transaction->id)
            ->where('status', 'open')
            ->get();

        foreach ($existingOpenTasks as $openTask) {
            if (! in_array($openTask->deficiency_type, $missingParts, true)) {
                $openTask->update([
                    'status' => 'resolved',
                    'resolved_at' => now(),
                    'resolved_by_user_id' => auth()->id(),
                    'resolution_notes' => __('services_tracking.reconciliation.resolved_auto'),
                ]);
            }
        }

        return UnifiedDeficiencyTask::where('service_transaction_id', $transaction->id)->get();
    }

    /**
     * Resolve a deficiency task manually and re-evaluate reconciliation on the transaction.
     */
    public static function resolveTask(
        UnifiedDeficiencyTask $task,
        ?string $notes = null,
        ?int $userId = null,
    ): void {
        DB::transaction(function () use ($task, $notes, $userId) {
            $task->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'resolved_by_user_id' => $userId ?? auth()->id(),
                'resolution_notes' => $notes,
            ]);

            OperationsAuditService::record(
                companyId: $task->company_id ?? 1,
                transactionId: $task->service_transaction_id,
                actionType: 'task_resolved',
                oldStatus: 'open',
                newStatus: 'resolved',
                payload: [
                    'task_code' => $task->task_code,
                    'deficiency_type' => $task->deficiency_type,
                    'resolution_notes' => $notes,
                ],
                userId: $userId ?? auth()->id(),
            );

            if ($task->transaction) {
                static::reconcile($task->transaction);
            }
        });
    }

    /**
     * Execute batch reconciliation across all open transactions for a company.
     *
     * @return array{total: int, matched: int, mismatched: int}
     */
    public static function runBatchReconciliation(int $companyId): array
    {
        $transactions = ServiceTransaction::where('company_id', $companyId)
            ->where('service_status', '!=', 'completed')
            ->get();

        $matchedCount = 0;
        $mismatchedCount = 0;

        foreach ($transactions as $tx) {
            $reconciled = static::reconcile($tx);
            if ($reconciled->workflow_status === OperationWorkflowStatus::COMPLETED->value) {
                $matchedCount++;
            } else {
                $mismatchedCount++;
            }
        }

        return [
            'total' => $transactions->count(),
            'matched' => $matchedCount,
            'mismatched' => $mismatchedCount,
        ];
    }

    /**
     * Execute reconciliation across a given collection of transactions.
     *
     * @param  \Illuminate\Support\Collection<int, ServiceTransaction>  $transactions
     * @return array{matched: int, mismatched: int}
     */
    public static function reconcileCollection(\Illuminate\Support\Collection $transactions): array
    {
        $matched = 0;
        $mismatched = 0;

        foreach ($transactions as $tx) {
            $reconciled = static::reconcile($tx);
            if ($reconciled->workflow_status === OperationWorkflowStatus::COMPLETED->value) {
                $matched++;
            } else {
                $mismatched++;
            }
        }

        return [
            'matched' => $matched,
            'mismatched' => $mismatched,
        ];
    }
}
