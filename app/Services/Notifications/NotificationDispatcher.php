<?php

namespace App\Services\Notifications;

use App\Filament\Resources\Mail\MailAccounts\MailAccountResource;
use App\Filament\Resources\Mail\MailMessages\MailMessageResource;
use App\Filament\Resources\TreasuryTransactions\TreasuryTransactionResource;
use App\Models\CashHandover;
use App\Models\Company;
use App\Models\Debt;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Leave;
use App\Models\Mail\MailAccount;
use App\Models\Mail\MailMessage;
use App\Models\PerformanceEscalation;
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
    public static function send(
        Model $record,
        BaseSystemNotification $notification,
        array $excludeUserIds = [],
        bool $synchronous = false,
    ): int {
        $recipients = NotificationRecipientService::getQualifiedRecipients($record, excludeUserIds: $excludeUserIds);
        $notification->recordType = $record::class;
        $notification->recordId = $record->getKey();
        $notification->actionUrl ??= static::actionUrl($record);

        if ($recipients->isEmpty()) {
            return 0;
        }

        if ($synchronous) {
            Notification::sendNow($recipients, $notification);
        } else {
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
        bool $synchronous = false,
    ): int {
        return static::send($record, new BaseSystemNotification(
            eventKey: $eventKey,
            customTitle: __($titleKey, $parameters),
            customMessage: __($bodyKey, $parameters),
            icon: $icon,
            color: $color,
        ), $excludeUserIds, $synchronous);
    }

    public static function actionUrl(Model $record): ?string
    {
        if ($record instanceof MailMessage) {
            return MailMessageResource::getUrl('view', ['record' => $record]);
        }
        if ($record instanceof MailAccount) {
            return MailAccountResource::getUrl('edit', ['record' => $record]);
        }

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
            $record instanceof PerformanceEscalation => 'PerformanceEscalations\\PerformanceEscalation',
            default => null,
        };

        if ($module === null) {
            return null;
        }

        $resource = 'App\\Filament\\Resources\\'.$module.'Resource';

        try {
            $page = ($record instanceof Ticket || $record instanceof PerformanceEscalation) ? 'view' : 'edit';

            return $resource::getUrl($page, ['record' => $record]);
        } catch (\Throwable) {
            return null;
        }
    }
}
