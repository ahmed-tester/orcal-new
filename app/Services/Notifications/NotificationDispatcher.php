<?php

namespace App\Services\Notifications;

use App\Filament\Resources\TreasuryTransactions\TreasuryTransactionResource;
use App\Models\CashHandover;
use App\Models\Company;
use App\Models\Debt;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Leave;
use App\Models\ServiceTransaction;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\Ticket;
use App\Models\Violation;
use App\Notifications\BaseSystemNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

class NotificationDispatcher
{
    public static function send(Model $record, BaseSystemNotification $notification, array $excludeUserIds = []): int
    {
        $recipients = NotificationRecipientService::getQualifiedRecipients($record, excludeUserIds: $excludeUserIds);
        $notification->recordType = $record::class;
        $notification->recordId = $record->getKey();
        $notification->actionUrl ??= static::actionUrl($record);

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, $notification);
        }

        return $recipients->count();
    }

    public static function translated(
        Model $record,
        string $eventKey,
        string $titleKey,
        string $bodyKey,
        array $parameters = [],
        string $icon = 'heroicon-o-bell',
        string $color = 'primary',
        array $excludeUserIds = [],
    ): int {
        return static::send($record, new BaseSystemNotification(
            eventKey: $eventKey,
            customTitle: __($titleKey, $parameters),
            customMessage: __($bodyKey, $parameters),
            icon: $icon,
            color: $color,
        ), $excludeUserIds);
    }

    public static function actionUrl(Model $record): ?string
    {
        if ($record instanceof CashHandover) {
            try {
                return TreasuryTransactionResource::getUrl('index');
            } catch (\Throwable) {
                return null;
            }
        }

        $module = match (true) {
            $record instanceof Employee => in_array('update_foreign_employee', NotificationRecipientService::resolveDefaultUpdatePermissions($record), true)
                ? 'ForeignEmployees\\ForeignEmployee' : 'SaudiEmployees\\SaudiEmployee',
            $record instanceof Company => 'Companies\\Company',
            $record instanceof Leave => 'Leaves\\Leave',
            $record instanceof Debt => 'Debts\\Debt',
            $record instanceof Violation => 'Violations\\Violation',
            $record instanceof Task => 'Tasks\\Task',
            $record instanceof TaskAssignment => 'TaskAssignments\\TaskAssignment',
            $record instanceof Invoice => 'Invoices\\Invoice',
            $record instanceof Ticket => 'Tickets\\Ticket',
            $record instanceof ServiceTransaction => 'ServiceTransactions\\ServiceTransaction',
            default => null,
        };

        if ($module === null) {
            return null;
        }

        $resource = 'App\\Filament\\Resources\\'.$module.'Resource';

        try {
            return $resource::getUrl($record instanceof Ticket ? 'view' : 'edit', ['record' => $record]);
        } catch (\Throwable) {
            return null;
        }
    }
}
