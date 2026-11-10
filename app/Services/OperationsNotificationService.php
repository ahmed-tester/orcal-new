<?php

namespace App\Services;

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
