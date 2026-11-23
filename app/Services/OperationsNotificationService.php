<?php

namespace App\Services;

use App\Enums\FinancialDisbursementStatus;
use App\Enums\FinancialDisbursementType;
use App\Enums\FinancialReceiptStatus;
use App\Models\CashHandover;
use App\Models\CashHandoverItem;
use App\Models\Employee;
use App\Models\FinancialDisbursement;
use App\Models\FinancialDisbursementPayment;
use App\Models\FinancialReceipt;
use App\Models\Invoice;
use App\Models\ServiceTransaction;
use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\NotificationRecipientService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class OperationsNotificationService
{
    public static function getOperationsManagers(ServiceTransaction $transaction, array $excludeUserIds = []): Collection
    {
        return NotificationRecipientService::getQualifiedRecipients($transaction, excludeUserIds: $excludeUserIds);
    }

    /**
     * N01: Notify the newly assigned executor on transaction creation or executor change.
     */
    public static function notifyAssigned(ServiceTransaction $transaction, ?int $actorId = null): int
    {
        $executorEmployeeId = (int) ($transaction->executed_by ?? 0);
        if ($executorEmployeeId <= 0) {
            return 0;
        }

        $executorUserId = static::resolveEmployeeUserId($executorEmployeeId, $transaction->executor);
        if (! $executorUserId) {
            return 0;
        }

        $dueDate = $transaction->due_date
            ? Carbon::parse($transaction->due_date)->format('Y-m-d')
            : '—';

        return static::dispatchTargeted(
            record: $transaction,
            event: 'ServiceTransactionAssigned',
            key: 'assigned',
            parameters: [
                'service' => $transaction->display_service_name,
                'due_date' => $dueDate,
            ],
            icon: 'heroicon-o-user-plus',
            color: 'info',
            excludeUserIds: array_filter([$actorId]),
            context: [
                'target_user_ids' => [$executorUserId],
                'expected_executor_employee_id' => $executorEmployeeId,
            ],
        );
    }

    /**
     * N02: Notify the executor when an active transaction passes its due date.
     */
    public static function notifyOverdue(ServiceTransaction $transaction, int $daysOverdue, bool $synchronous = false): int
    {
        $executorEmployeeId = (int) ($transaction->executed_by ?? 0);
        if ($executorEmployeeId <= 0) {
            return 0;
        }

        $executorUserId = static::resolveEmployeeUserId($executorEmployeeId, $transaction->executor);
        if (! $executorUserId) {
            return 0;
        }

        $dueDate = $transaction->due_date
            ? Carbon::parse($transaction->due_date)->format('Y-m-d')
            : '—';

        return static::dispatchTargeted(
            record: $transaction,
            event: 'ServiceTransactionOverdue',
            key: 'overdue',
            parameters: [
                'service' => $transaction->display_service_name,
                'due_date' => $dueDate,
                'days' => max(1, $daysOverdue),
            ],
            icon: 'heroicon-o-clock',
            color: 'danger',
            excludeUserIds: [],
            context: [
                'target_user_ids' => [$executorUserId],
                'expected_executor_employee_id' => $executorEmployeeId,
            ],
            synchronous: $synchronous,
        );
    }

    /**
     * N09: Notify the transaction creator when the transaction transitions to completed,
     * only if the creator is different from the executor.
     */
    public static function notifyCompleted(ServiceTransaction $transaction, ?int $actorId = null): int
    {
        $creatorUserId = static::resolveTransactionCreatorUserId($transaction);
        if (! $creatorUserId) {
            return 0;
        }

        $executorUserId = static::resolveEmployeeUserId((int) ($transaction->executed_by ?? 0), $transaction->executor);
        if ($executorUserId && (int) $creatorUserId === (int) $executorUserId) {
            return 0;
        }

        $completionDate = $transaction->completion_date
            ? Carbon::parse($transaction->completion_date)->format('Y-m-d')
            : now()->toDateString();

        return static::dispatchTargeted(
            record: $transaction,
            event: 'ServiceTransactionCompleted',
            key: 'completed',
            parameters: [
                'service' => $transaction->display_service_name,
                'completion_date' => $completionDate,
            ],
            icon: 'heroicon-o-check-circle',
            color: 'success',
            excludeUserIds: array_filter([$actorId]),
            context: [
                'target_user_ids' => [$creatorUserId],
            ],
        );
    }

    /**
     * N11: Escalate transaction to users holding resolve_service_escalation in scope.
     */
    public static function notifyEscalated(ServiceTransaction $transaction, array $escalationData, ?int $actorId = null): void
    {
        $actorId ??= auth()->id();
        $actorName = ($actorId ? User::find($actorId)?->name : auth()->user()?->name)
            ?? __('services_tracking.dashboard.executor');

        static::dispatchTargeted(
            record: $transaction,
            event: 'ServiceTransactionEscalated',
            key: 'escalated',
            parameters: [
                'reason' => __('services_tracking.escalation.reason_'.($escalationData['reason'] ?? 'other')),
                'user' => $actorName,
            ],
            icon: 'heroicon-o-exclamation-triangle',
            color: 'danger',
            excludeUserIds: array_filter([$actorId]),
            context: [
                'required_permission' => 'resolve_service_escalation',
                'is_action_request' => true,
            ],
        );
    }

    /**
     * N12: Notify the executor and the escalator when an escalation is resolved.
     */
    public static function notifyEscalationResolved(
        ServiceTransaction $transaction,
        ?string $resolutionNotes,
        ?int $actorId = null,
        ?int $escalatedByUserId = null,
    ): void {
        $actorId ??= auth()->id();
        $executorUserId = static::resolveEmployeeUserId((int) ($transaction->executed_by ?? 0), $transaction->executor);
        $escalatorId = $escalatedByUserId ?? (isset($transaction->metadata['escalated_by']) ? (int) $transaction->metadata['escalated_by'] : null);

        $targetUserIds = array_values(array_unique(array_filter([
            $executorUserId ? (int) $executorUserId : null,
            $escalatorId ? (int) $escalatorId : null,
        ])));

        if ($targetUserIds === []) {
            return;
        }

        static::dispatchTargeted(
            record: $transaction,
            event: 'ServiceTransactionEscalationResolved',
            key: 'escalation_resolved',
            parameters: [
                'notes' => $resolutionNotes ?: '—',
            ],
            icon: 'heroicon-o-check-badge',
            color: 'success',
            excludeUserIds: array_filter([$actorId]),
            context: [
                'target_user_ids' => $targetUserIds,
            ],
        );
    }

    /**
     * Legacy custody handover confirmation: notify the transaction's executor.
     */
    public static function notifyCustodyReceived(ServiceTransaction $transaction, float $amount, ?int $receivedByUserId = null): void
    {
        $actorId = $receivedByUserId ?? auth()->id();
        $executorUserId = static::resolveEmployeeUserId((int) ($transaction->executed_by ?? 0), $transaction->executor);

        if (! $executorUserId) {
            return;
        }

        static::dispatchTargeted(
            record: $transaction,
            event: 'ServiceTransactionCustodyReceived',
            key: 'custody_received',
            parameters: [
                'amount' => number_format($amount, 2),
                'receiver' => ($actorId ? User::find($actorId)?->name : auth()->user()?->name) ?? '—',
            ],
            icon: 'heroicon-o-banknotes',
            color: 'success',
            excludeUserIds: array_filter([$actorId]),
            context: [
                'target_user_ids' => [$executorUserId],
            ],
        );
    }

    /**
     * N13: Notify only the responsible employee when new deficiency tasks are created.
     */
    public static function notifyDeficiencyTasksCreated(
        ServiceTransaction $transaction,
        array $missingParts,
        ?int $actorId = null,
    ): void {
        if ($missingParts === []) {
            return;
        }

        $responsibleEmployeeId = (int) ($transaction->executed_by ?? $transaction->employee_id ?? 0);
        if ($responsibleEmployeeId <= 0) {
            return;
        }

        $responsibleUserId = static::resolveEmployeeUserId($responsibleEmployeeId);
        if (! $responsibleUserId) {
            return;
        }

        static::dispatchTargeted(
            record: $transaction,
            event: 'ServiceTransactionDeficiencyTasks',
            key: 'deficiency_tasks',
            parameters: [
                'count' => count($missingParts),
            ],
            icon: 'heroicon-o-clipboard-document-list',
            color: 'warning',
            excludeUserIds: array_filter([$actorId]),
            context: [
                'target_user_ids' => [$responsibleUserId],
                'expected_executor_employee_id' => $responsibleEmployeeId,
            ],
        );
    }

    /**
     * N03: Notify users authorized with confirm_service_receipt when a receipt requires confirmation.
     */
    public static function notifyFinancialReceiptRecorded(
        ServiceTransaction $transaction,
        FinancialReceipt $receipt,
        ?User $user = null,
    ): void {
        if ($receipt->status !== FinancialReceiptStatus::PENDING_CONFIRMATION) {
            return;
        }

        $actorId = $user?->id ?? $receipt->created_by ?? auth()->id();

        static::dispatchTargeted(
            record: $transaction,
            event: 'ServiceFinancialReceiptRecorded',
            key: 'financial_receipt_recorded',
            parameters: [
                'receipt' => $receipt->receipt_number,
                'amount' => number_format((float) $receipt->amount, 2),
                'method' => $receipt->payment_method?->getLabel() ?? '—',
            ],
            icon: 'heroicon-o-receipt-percent',
            color: 'info',
            excludeUserIds: array_filter([$actorId]),
            context: [
                'required_permission' => 'confirm_service_receipt',
                'subject_type' => FinancialReceipt::class,
                'subject_id' => $receipt->id,
                'company_id' => (int) ($receipt->company_id ?? $transaction->company_id),
                'branch_id' => $receipt->branch_id ?? NotificationRecipientService::resolveBranchId($transaction),
                'is_action_request' => true,
            ],
        );
    }

    /**
     * Notify treasury, operations managers, and higher management when a cash receipt enters employee custody.
     */
    public static function notifyFinancialReceiptConfirmed(ServiceTransaction $transaction, FinancialReceipt $receipt): void
    {
        if (! $receipt->isCash() || $receipt->status !== FinancialReceiptStatus::CONFIRMED) {
            return;
        }

        $actorId = $receipt->created_by ?? auth()->id();
        $employeeName = $receipt->collector?->user?->name
            ?? $transaction->executor?->user?->name
            ?? '—';

        $invoiceId = $transaction->metadata['auto_invoice_id'] ?? null;
        $invoiceNumber = $invoiceId
            ? (Invoice::query()->whereKey((int) $invoiceId)->value('invoice_number') ?? '—')
            : '—';

        static::dispatchTargeted(
            record: $transaction,
            event: 'ServiceFinancialReceiptConfirmed',
            key: 'financial_cash_custody_recorded',
            parameters: [
                'receipt' => (string) $receipt->receipt_number,
                'invoice' => (string) $invoiceNumber,
                'employee' => $employeeName,
                'amount' => number_format((float) $receipt->amount, 2),
                'method' => $receipt->payment_method?->getLabel() ?? '—',
            ],
            icon: 'heroicon-o-banknotes',
            color: 'warning',
            excludeUserIds: array_filter([$actorId ? (int) $actorId : null]),
            context: [
                'required_permission' => ['confirm_cash_handover', 'approve_service_transaction'],
                'include_higher_management' => true,
                'subject_type' => FinancialReceipt::class,
                'subject_id' => $receipt->id,
                'company_id' => (int) ($receipt->company_id ?? $transaction->company_id),
                'branch_id' => $receipt->branch_id ?? NotificationRecipientService::resolveBranchId($transaction),
                'is_action_request' => true,
            ],
        );
    }

    /**
     * N04: Notify users authorized with confirm_cash_handover when a cash handover is submitted.
     */
    public static function notifyCashHandoverRequested(CashHandover $handover, ?int $actorId = null): void
    {
        $actorId ??= $handover->requested_by ?? ($handover->metadata['submitted_by'] ?? null) ?? auth()->id();

        NotificationDispatcher::translated(
            record: $handover,
            eventKey: 'ServiceFinancialHandoverRequested',
            titleKey: 'services_tracking.notifications.financial_handover_requested_title',
            bodyKey: 'services_tracking.notifications.financial_handover_requested_body',
            parameters: [
                'amount' => number_format((float) $handover->total_requested_amount, 2),
            ],
            icon: 'heroicon-o-inbox-arrow-down',
            color: 'warning',
            excludeUserIds: array_filter([(int) $actorId]),
            context: [
                'routing_mode' => 'operations_targeted',
                'event_key' => 'ServiceFinancialHandoverRequested',
                'required_permission' => 'confirm_cash_handover',
                'subject_type' => CashHandover::class,
                'subject_id' => $handover->id,
                'company_id' => (int) $handover->company_id,
                'branch_id' => $handover->branch_id,
                'is_action_request' => true,
            ],
        );
    }

    /**
     * N05 (Confirmed): Notify the collector/deliverer who handed over the cash.
     */
    public static function notifyCashHandoverConfirmed(CashHandover $handover, ?int $actorId = null): void
    {
        $actorId ??= $handover->received_by ?? ($handover->metadata['received_by'] ?? null) ?? auth()->id();
        $targetUserIds = static::resolveHandoverCustodyUserIds($handover);

        if ($targetUserIds === []) {
            return;
        }

        $transaction = static::handoverTransaction($handover);
        $record = $transaction ?? $handover;

        static::dispatchTargeted(
            record: $record,
            event: 'ServiceFinancialHandoverConfirmed',
            key: 'financial_handover_confirmed',
            parameters: [
                'handover' => $handover->handover_number,
                'amount' => number_format((float) $handover->total_confirmed_amount, 2),
                'difference' => number_format((float) $handover->difference_amount, 2),
                'number' => $transaction?->transaction_number ?? $handover->handover_number,
            ],
            icon: 'heroicon-o-check-badge',
            color: 'success',
            excludeUserIds: array_filter([(int) $actorId]),
            context: [
                'target_user_ids' => $targetUserIds,
                'subject_type' => CashHandover::class,
                'subject_id' => $handover->id,
                'transaction_id' => $transaction?->id,
                'company_id' => (int) $handover->company_id,
                'branch_id' => $handover->branch_id,
            ],
        );
    }

    /**
     * N05 (Rejected): Notify the collector/deliverer when their handover voucher is rejected.
     */
    public static function notifyCashHandoverRejected(CashHandover $handover, string $reason, ?int $actorId = null): void
    {
        $actorId ??= ($handover->metadata['rejected_by'] ?? null) ?? auth()->id();
        $targetUserIds = static::resolveHandoverCustodyUserIds($handover);

        if ($targetUserIds === []) {
            return;
        }

        $transaction = static::handoverTransaction($handover);
        $record = $transaction ?? $handover;

        static::dispatchTargeted(
            record: $record,
            event: 'ServiceFinancialHandoverRejected',
            key: 'financial_handover_rejected',
            parameters: [
                'handover' => $handover->handover_number,
                'reason' => $reason,
                'number' => $transaction?->transaction_number ?? $handover->handover_number,
            ],
            icon: 'heroicon-o-x-circle',
            color: 'danger',
            excludeUserIds: array_filter([(int) $actorId]),
            context: [
                'target_user_ids' => $targetUserIds,
                'subject_type' => CashHandover::class,
                'subject_id' => $handover->id,
                'transaction_id' => $transaction?->id,
                'company_id' => (int) $handover->company_id,
                'branch_id' => $handover->branch_id,
            ],
        );
    }

    /**
     * N06: Notify users authorized with approve_self_paid_disbursement when an employee personal claim is recorded.
     * Direct treasury disbursements require no approval and do not emit a general registration alert.
     */
    public static function notifyFinancialDisbursementRecorded(
        ServiceTransaction $transaction,
        FinancialDisbursement $disbursement,
        ?int $actorId = null,
    ): void {
        if ($disbursement->disbursement_type !== FinancialDisbursementType::EMPLOYEE_PERSONAL
            || $disbursement->status !== FinancialDisbursementStatus::PENDING_APPROVAL) {
            return;
        }

        $actorId ??= $disbursement->created_by ?? auth()->id();
        $claimantUserId = static::resolveEmployeeUserId((int) ($disbursement->paid_by_employee_id ?? 0), $disbursement->paidByEmployee);
        $employeeName = $disbursement->paidByEmployee?->user?->name ?? '—';

        static::dispatchTargeted(
            record: $transaction,
            event: 'ServiceFinancialDisbursementRecorded',
            key: 'financial_disbursement_recorded',
            parameters: [
                'claim' => $disbursement->disbursement_number,
                'employee' => $employeeName,
                'amount' => number_format((float) $disbursement->amount, 2),
                'type' => $disbursement->disbursement_type?->getLabel() ?? '—',
            ],
            icon: 'heroicon-o-document-text',
            color: 'warning',
            excludeUserIds: array_filter([(int) $actorId, $claimantUserId ? (int) $claimantUserId : null]),
            context: [
                'required_permission' => 'approve_self_paid_disbursement',
                'subject_type' => FinancialDisbursement::class,
                'subject_id' => $disbursement->id,
                'company_id' => (int) ($disbursement->company_id ?? $transaction->company_id),
                'branch_id' => $disbursement->branch_id ?? NotificationRecipientService::resolveBranchId($transaction),
                'is_action_request' => true,
            ],
        );
    }

    /**
     * N06 (Legacy path): Notify users with approve_self_paid_disbursement when a legacy self-paid fee is recorded.
     */
    public static function notifyLegacySelfPaidRecorded(
        ServiceTransaction $transaction,
        float $amount,
        ?int $actorId = null,
    ): void {
        $actorId ??= auth()->id();
        $claimantUserId = static::resolveEmployeeUserId((int) ($transaction->executed_by ?? 0), $transaction->executor);
        $employeeName = $transaction->executor?->user?->name ?? '—';

        static::dispatchTargeted(
            record: $transaction,
            event: 'ServiceFinancialDisbursementRecorded',
            key: 'financial_disbursement_recorded',
            parameters: [
                'claim' => $transaction->transaction_number,
                'employee' => $employeeName,
                'amount' => number_format($amount, 2),
                'type' => __('services_tracking.disbursement.self_paid_badge'),
            ],
            icon: 'heroicon-o-document-text',
            color: 'warning',
            excludeUserIds: array_filter([(int) $actorId, $claimantUserId ? (int) $claimantUserId : null]),
            context: [
                'required_permission' => 'approve_self_paid_disbursement',
                'is_action_request' => true,
            ],
        );
    }

    /**
     * N07 (Legacy path): Notify users with pay_employee_reimbursement when a legacy self-paid fee is approved.
     */
    public static function notifyLegacySelfPaidApproved(
        ServiceTransaction $transaction,
        float $amount,
        ?int $actorId = null,
    ): void {
        $actorId ??= ($transaction->metadata['self_paid_approved_by'] ?? null) ?? auth()->id();
        $employeeName = $transaction->executor?->user?->name ?? '—';

        static::dispatchTargeted(
            record: $transaction,
            event: 'ServiceFinancialClaimApproved',
            key: 'financial_claim_approved',
            parameters: [
                'claim' => $transaction->transaction_number,
                'employee' => $employeeName,
                'amount' => number_format($amount, 2),
            ],
            icon: 'heroicon-o-check-circle',
            color: 'success',
            excludeUserIds: array_filter([(int) $actorId]),
            context: [
                'required_permission' => 'pay_employee_reimbursement',
                'is_action_request' => true,
            ],
        );
    }

    /**
     * N07: Notify users authorized with pay_employee_reimbursement when a personal claim is approved.
     */
    public static function notifyFinancialClaimApproved(
        ServiceTransaction $transaction,
        FinancialDisbursement $disbursement,
        ?int $actorId = null,
    ): void {
        $actorId ??= $disbursement->approved_by ?? auth()->id();
        $employeeName = $disbursement->paidByEmployee?->user?->name ?? '—';

        static::dispatchTargeted(
            record: $transaction,
            event: 'ServiceFinancialClaimApproved',
            key: 'financial_claim_approved',
            parameters: [
                'claim' => $disbursement->disbursement_number,
                'employee' => $employeeName,
                'amount' => number_format((float) $disbursement->amount, 2),
            ],
            icon: 'heroicon-o-check-circle',
            color: 'success',
            excludeUserIds: array_filter([(int) $actorId]),
            context: [
                'required_permission' => 'pay_employee_reimbursement',
                'subject_type' => FinancialDisbursement::class,
                'subject_id' => $disbursement->id,
                'company_id' => (int) ($disbursement->company_id ?? $transaction->company_id),
                'branch_id' => $disbursement->branch_id ?? NotificationRecipientService::resolveBranchId($transaction),
                'is_action_request' => true,
            ],
        );
    }

    /**
     * N08: Notify only the claim owner employee (paid_by_employee_id) when a reimbursement payment is paid.
     */
    public static function notifyFinancialReimbursementPaid(
        ServiceTransaction $transaction,
        FinancialDisbursement $disbursement,
        FinancialDisbursementPayment $payment,
        ?int $actorId = null,
    ): void {
        $actorId ??= $payment->paid_by ?? auth()->id();
        $claimOwnerUserId = static::resolveEmployeeUserId((int) ($disbursement->paid_by_employee_id ?? 0), $disbursement->paidByEmployee);

        if (! $claimOwnerUserId) {
            return;
        }

        static::dispatchTargeted(
            record: $transaction,
            event: 'ServiceFinancialReimbursementPaid',
            key: 'financial_reimbursement_paid',
            parameters: [
                'claim' => $disbursement->disbursement_number,
                'payment' => $payment->payment_number,
                'amount' => number_format((float) $payment->amount, 2),
                'remaining' => number_format($disbursement->remainingDue(), 2),
            ],
            icon: 'heroicon-o-wallet',
            color: 'success',
            excludeUserIds: array_filter([(int) $actorId]),
            context: [
                'target_user_ids' => [$claimOwnerUserId],
                'subject_type' => FinancialDisbursementPayment::class,
                'subject_id' => $payment->id,
                'company_id' => (int) ($payment->company_id ?? $transaction->company_id),
                'branch_id' => $payment->branch_id ?? NotificationRecipientService::resolveBranchId($transaction),
            ],
        );
    }

    /**
     * N10 (Receipt Reversal): Notify reverse_financial_transaction holders + the collector employee.
     */
    public static function notifyFinancialReceiptReversed(
        ServiceTransaction $transaction,
        FinancialReceipt $receipt,
        string $reason,
        ?int $actorId = null,
    ): void {
        $actorId ??= ($receipt->metadata['reversed_by'] ?? null) ?? auth()->id();
        $collectorUserId = static::resolveEmployeeUserId((int) ($receipt->collector_employee_id ?? 0), $receipt->collector);

        static::dispatchReversal(
            record: $transaction,
            subject: $receipt,
            documentType: __('services_tracking.notifications.reversal_types.receipt'),
            documentNumber: (string) $receipt->receipt_number,
            transactionNumber: (string) $transaction->transaction_number,
            amount: (float) $receipt->amount,
            reason: $reason,
            affectedUserIds: array_filter([$collectorUserId]),
            actorId: $actorId ? (int) $actorId : null,
            companyId: (int) ($receipt->company_id ?? $transaction->company_id),
            branchId: $receipt->branch_id ?? NotificationRecipientService::resolveBranchId($transaction),
        );
    }

    /**
     * N10 (Handover Reversal): Notify reverse_financial_transaction holders + the collector/deliverer employee.
     */
    public static function notifyCashHandoverReversed(
        CashHandover $handover,
        string $reason,
        ?int $actorId = null,
    ): void {
        $actorId ??= ($handover->metadata['reversed_by'] ?? null) ?? auth()->id();
        $affectedUserIds = static::resolveHandoverCustodyUserIds($handover);
        $transaction = static::handoverTransaction($handover);
        $record = $transaction ?? $handover;
        $amount = round((float) $handover->total_confirmed_amount + (float) ($handover->metadata['extra_amount'] ?? 0), 2);

        static::dispatchReversal(
            record: $record,
            subject: $handover,
            documentType: __('services_tracking.notifications.reversal_types.handover'),
            documentNumber: (string) $handover->handover_number,
            transactionNumber: (string) ($transaction?->transaction_number ?? $handover->handover_number),
            amount: $amount,
            reason: $reason,
            affectedUserIds: $affectedUserIds,
            actorId: $actorId ? (int) $actorId : null,
            companyId: (int) $handover->company_id,
            branchId: $handover->branch_id,
            transactionId: $transaction?->id,
        );
    }

    /**
     * N10 (Direct Disbursement Reversal): Notify reverse_financial_transaction holders in scope.
     */
    public static function notifyFinancialDirectDisbursementReversed(
        ServiceTransaction $transaction,
        FinancialDisbursement $disbursement,
        string $reason,
        ?int $actorId = null,
    ): void {
        $actorId ??= ($disbursement->metadata['reversed_by'] ?? null) ?? auth()->id();

        static::dispatchReversal(
            record: $transaction,
            subject: $disbursement,
            documentType: __('services_tracking.notifications.reversal_types.direct_disbursement'),
            documentNumber: (string) $disbursement->disbursement_number,
            transactionNumber: (string) $transaction->transaction_number,
            amount: (float) $disbursement->amount,
            reason: $reason,
            affectedUserIds: [],
            actorId: $actorId ? (int) $actorId : null,
            companyId: (int) ($disbursement->company_id ?? $transaction->company_id),
            branchId: $disbursement->branch_id ?? NotificationRecipientService::resolveBranchId($transaction),
        );
    }

    /**
     * N10 (Reimbursement Payment Reversal): Notify reverse_financial_transaction holders + the claim owner employee.
     */
    public static function notifyFinancialReimbursementReversed(
        ServiceTransaction $transaction,
        FinancialDisbursement $disbursement,
        FinancialDisbursementPayment $payment,
        string $reason,
        ?int $actorId = null,
    ): void {
        $actorId ??= ($payment->metadata['reversed_by'] ?? null) ?? auth()->id();
        $claimOwnerUserId = static::resolveEmployeeUserId((int) ($disbursement->paid_by_employee_id ?? 0), $disbursement->paidByEmployee);

        static::dispatchReversal(
            record: $transaction,
            subject: $payment,
            documentType: __('services_tracking.notifications.reversal_types.reimbursement_payment'),
            documentNumber: (string) $payment->payment_number,
            transactionNumber: (string) $transaction->transaction_number,
            amount: (float) $payment->amount,
            reason: $reason,
            affectedUserIds: array_filter([$claimOwnerUserId]),
            actorId: $actorId ? (int) $actorId : null,
            companyId: (int) ($payment->company_id ?? $transaction->company_id),
            branchId: $payment->branch_id ?? NotificationRecipientService::resolveBranchId($transaction),
        );
    }

    /**
     * @param  array<int>  $affectedUserIds
     */
    private static function dispatchReversal(
        Model $record,
        Model $subject,
        string $documentType,
        string $documentNumber,
        string $transactionNumber,
        float $amount,
        string $reason,
        array $affectedUserIds,
        ?int $actorId,
        ?int $companyId,
        ?int $branchId,
        ?int $transactionId = null,
    ): void {
        static::dispatchTargeted(
            record: $record,
            event: 'ServiceFinancialTransactionReversed',
            key: 'financial_reversed',
            parameters: [
                'document_type' => $documentType,
                'document_number' => $documentNumber,
                'number' => $transactionNumber,
                'amount' => number_format($amount, 2),
                'reason' => $reason,
            ],
            icon: 'heroicon-o-arrow-uturn-left',
            color: 'danger',
            excludeUserIds: array_filter([$actorId]),
            context: [
                'required_permission' => 'reverse_financial_transaction',
                'target_user_ids' => array_values(array_unique(array_filter(array_map('intval', $affectedUserIds)))),
                'subject_type' => $subject::class,
                'subject_id' => $subject->getKey(),
                'transaction_id' => $transactionId ?? ($record instanceof ServiceTransaction ? $record->id : null),
                'company_id' => $companyId,
                'branch_id' => $branchId,
            ],
        );
    }

    public static function resolveTransactionCreatorUserId(ServiceTransaction $transaction): ?int
    {
        $metaCreator = $transaction->metadata['created_by'] ?? null;
        if (is_numeric($metaCreator) && (int) $metaCreator > 0) {
            return (int) $metaCreator;
        }

        $auditCreator = $transaction->auditLogs()
            ->where('action_type', 'created')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->value('user_id');

        return $auditCreator ? (int) $auditCreator : null;
    }

    private static function resolveEmployeeUserId(int $employeeId, ?Employee $loadedEmployee = null): ?int
    {
        if ($employeeId <= 0) {
            return null;
        }

        if ($loadedEmployee && (int) $loadedEmployee->id === $employeeId && $loadedEmployee->user_id) {
            return (int) $loadedEmployee->user_id;
        }

        $userId = Employee::query()->whereKey($employeeId)->value('user_id');

        return $userId ? (int) $userId : null;
    }

    /**
     * @return array<int>
     */
    private static function resolveHandoverCustodyUserIds(CashHandover $handover): array
    {
        $collectorUserId = static::resolveEmployeeUserId((int) ($handover->collector_employee_id ?? 0), $handover->collector);
        $delivererUserId = static::resolveEmployeeUserId((int) ($handover->delivered_by_employee_id ?? 0), $handover->deliveredBy);

        return array_values(array_unique(array_filter([
            $collectorUserId,
            $delivererUserId,
        ])));
    }

    private static function handoverTransaction(CashHandover $handover): ?ServiceTransaction
    {
        $receiptId = CashHandoverItem::where('cash_handover_id', $handover->id)->value('financial_receipt_id');

        if (! $receiptId) {
            return null;
        }

        $receipt = FinancialReceipt::select(['id', 'source_type', 'source_id'])->find($receiptId);

        if (! $receipt || $receipt->source_type !== FinancialReceipt::SOURCE_SERVICE_TRANSACTION) {
            return null;
        }

        return $receipt->source_id ? ServiceTransaction::find($receipt->source_id) : null;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @param  array<int>  $excludeUserIds
     * @param  array<string, mixed>  $context
     */
    private static function dispatchTargeted(
        Model $record,
        string $event,
        string $key,
        array $parameters,
        string $icon,
        string $color,
        array $excludeUserIds = [],
        array $context = [],
        bool $synchronous = false,
    ): int {
        $baseParameters = $record instanceof ServiceTransaction
            ? ['number' => $record->transaction_number]
            : [];

        return NotificationDispatcher::translated(
            record: $record,
            eventKey: $event,
            titleKey: "services_tracking.notifications.{$key}_title",
            bodyKey: "services_tracking.notifications.{$key}_body",
            parameters: $parameters + $baseParameters,
            icon: $icon,
            color: $color,
            excludeUserIds: array_values(array_unique(array_filter(array_map('intval', $excludeUserIds)))),
            synchronous: $synchronous,
            context: array_merge([
                'routing_mode' => 'operations_targeted',
                'event_key' => $event,
            ], $context),
        );
    }
}
