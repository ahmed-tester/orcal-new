<?php

namespace App\Services;

use App\Models\CashHandover;
use App\Models\CashHandoverItem;
use App\Models\FinancialDisbursement;
use App\Models\FinancialDisbursementPayment;
use App\Models\FinancialReceipt;
use App\Models\ServiceTransaction;
use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\NotificationRecipientService;
use Illuminate\Support\Collection;

class OperationsNotificationService
{
    public static function getOperationsManagers(ServiceTransaction $transaction, array $excludeUserIds = []): Collection
    {
        return NotificationRecipientService::getQualifiedRecipients($transaction, excludeUserIds: $excludeUserIds);
    }

    public static function notifyEscalated(ServiceTransaction $transaction, array $escalationData): void
    {
        static::send($transaction, 'ServiceTransactionEscalated', 'escalated', [
            'reason' => __('services_tracking.escalation.reason_'.($escalationData['reason'] ?? 'other')),
            'user' => auth()->user()?->name ?? __('services_tracking.dashboard.executor'),
        ], 'heroicon-o-exclamation-triangle', 'danger', array_filter([auth()->id()]));
    }

    public static function notifyEscalationResolved(ServiceTransaction $transaction, ?string $resolutionNotes): void
    {
        static::send($transaction, 'ServiceTransactionEscalationResolved', 'escalation_resolved', [
            'notes' => $resolutionNotes ?: '—',
        ], 'heroicon-o-check-badge', 'success');
    }

    public static function notifyCustodyReceived(ServiceTransaction $transaction, float $amount, ?int $receivedByUserId = null): void
    {
        static::send($transaction, 'ServiceTransactionCustodyReceived', 'custody_received', [
            'amount' => number_format($amount, 2),
            'receiver' => ($receivedByUserId ? User::find($receivedByUserId)?->name : auth()->user()?->name) ?? '—',
        ], 'heroicon-o-banknotes', 'success');
    }

    public static function notifyDeficiencyTasksCreated(ServiceTransaction $transaction, array $missingParts): void
    {
        if ($missingParts !== []) {
            static::send($transaction, 'ServiceTransactionDeficiencyTasks', 'deficiency_tasks', [
                'count' => count($missingParts),
            ], 'heroicon-o-clipboard-document-list', 'warning');
        }
    }

    public static function notifyFinancialReceiptRecorded(ServiceTransaction $transaction, FinancialReceipt $receipt, ?User $user = null): void
    {
        static::send($transaction, 'ServiceFinancialReceiptRecorded', 'financial_receipt_recorded', [
            'receipt' => $receipt->receipt_number,
            'amount' => number_format((float) $receipt->amount, 2),
            'method' => $receipt->payment_method?->getLabel() ?? '—',
        ], 'heroicon-o-receipt-percent', 'info', array_filter([$user?->id]));
    }

    public static function notifyFinancialReceiptConfirmed(ServiceTransaction $transaction, FinancialReceipt $receipt): void
    {
        static::send($transaction, 'ServiceFinancialReceiptConfirmed', 'financial_receipt_confirmed', [
            'receipt' => $receipt->receipt_number,
            'amount' => number_format((float) $receipt->amount, 2),
            'method' => $receipt->payment_method?->getLabel() ?? '—',
        ], 'heroicon-o-banknotes', 'success');
    }

    public static function notifyCashHandoverRequested(CashHandover $handover): void
    {
        NotificationDispatcher::translated(
            record: $handover,
            eventKey: 'ServiceFinancialHandoverRequested',
            titleKey: 'services_tracking.notifications.financial_handover_requested_title',
            bodyKey: 'services_tracking.notifications.financial_handover_requested_body',
            parameters: [
                'handover' => $handover->handover_number,
                'amount' => number_format((float) $handover->total_requested_amount, 2),
            ],
            icon: 'heroicon-o-inbox-arrow-down',
            color: 'warning',
        );
    }

    public static function notifyCashHandoverConfirmed(CashHandover $handover): void
    {
        $transaction = static::handoverTransaction($handover);

        if (! $transaction) {
            return;
        }

        static::send($transaction, 'ServiceFinancialHandoverConfirmed', 'financial_handover_confirmed', [
            'handover' => $handover->handover_number,
            'amount' => number_format((float) $handover->total_confirmed_amount, 2),
            'difference' => number_format((float) $handover->difference_amount, 2),
        ], 'heroicon-o-check-badge', 'success');
    }

    public static function notifyCashHandoverRejected(CashHandover $handover, string $reason): void
    {
        $transaction = static::handoverTransaction($handover);

        if (! $transaction) {
            return;
        }

        static::send($transaction, 'ServiceFinancialHandoverRejected', 'financial_handover_rejected', [
            'handover' => $handover->handover_number,
            'reason' => $reason,
        ], 'heroicon-o-x-circle', 'danger');
    }

    public static function notifyFinancialDisbursementRecorded(ServiceTransaction $transaction, FinancialDisbursement $disbursement): void
    {
        static::send($transaction, 'ServiceFinancialDisbursementRecorded', 'financial_disbursement_recorded', [
            'number' => $disbursement->disbursement_number,
            'amount' => number_format((float) $disbursement->amount, 2),
            'type' => $disbursement->disbursement_type?->getLabel() ?? '—',
        ], 'heroicon-o-document-text', 'info');
    }

    public static function notifyFinancialClaimApproved(ServiceTransaction $transaction, FinancialDisbursement $disbursement): void
    {
        static::send($transaction, 'ServiceFinancialClaimApproved', 'financial_claim_approved', [
            'number' => $disbursement->disbursement_number,
            'amount' => number_format((float) $disbursement->amount, 2),
        ], 'heroicon-o-check-circle', 'success');
    }

    public static function notifyFinancialReimbursementPaid(
        ServiceTransaction $transaction,
        FinancialDisbursement $disbursement,
        FinancialDisbursementPayment $payment,
    ): void {
        static::send($transaction, 'ServiceFinancialReimbursementPaid', 'financial_reimbursement_paid', [
            'number' => $disbursement->disbursement_number,
            'payment' => $payment->payment_number,
            'amount' => number_format((float) $payment->amount, 2),
            'remaining' => number_format($disbursement->remainingDue(), 2),
        ], 'heroicon-o-wallet', 'success');
    }

    private static function handoverTransaction(CashHandover $handover): ?ServiceTransaction
    {
        $receiptId = CashHandoverItem::where('cash_handover_id', $handover->id)->value('financial_receipt_id');

        if (! $receiptId) {
            return null;
        }

        $sourceId = FinancialReceipt::whereKey($receiptId)->value('source_id');

        return $sourceId ? ServiceTransaction::find($sourceId) : null;
    }

    private static function send(
        ServiceTransaction $transaction,
        string $event,
        string $key,
        array $parameters,
        string $icon,
        string $color,
        array $excludeUserIds = [],
    ): void {
        NotificationDispatcher::translated(
            record: $transaction,
            eventKey: $event,
            titleKey: "services_tracking.notifications.{$key}_title",
            bodyKey: "services_tracking.notifications.{$key}_body",
            parameters: $parameters + ['number' => $transaction->transaction_number],
            icon: $icon,
            color: $color,
            excludeUserIds: $excludeUserIds,
        );
    }
}
