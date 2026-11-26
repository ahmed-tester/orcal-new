<?php

namespace App\Services\Notifications;

use App\Filament\Resources\FinancialApprovalRequests\FinancialApprovalRequestResource;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Mail\MailAccounts\MailAccountResource;
use App\Filament\Resources\Mail\MailMessages\MailMessageResource;
use App\Filament\Resources\PayrollRecords\PayrollRecordResource;
use App\Filament\Resources\ServiceTransactions\ServiceTransactionResource;
use App\Filament\Resources\TreasuryTransactions\TreasuryTransactionResource;
use App\Models\CashHandover;
use App\Models\Company;
use App\Models\Debt;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Leave;
use App\Models\Mail\MailAccount;
use App\Models\Mail\MailMessage;
use App\Models\Payroll\FinancialApprovalRequest;
use App\Models\Payroll\PayrollRecord;
use App\Models\PerformanceEscalation;
use App\Models\ServiceTransaction;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Violation;
use App\Notifications\BaseSystemNotification;
use App\Services\Payroll\PayrollApprovalScopeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

class NotificationDispatcher
{
    public static function send(
        Model $record,
        BaseSystemNotification $notification,
        array $excludeUserIds = [],
        bool $synchronous = false,
    ): int {
        $mergedExcluded = array_values(array_unique(array_filter(array_map(
            'intval',
            array_merge($excludeUserIds, (array) ($notification->context['exclude_user_ids'] ?? [])),
        ))));

        if ($mergedExcluded !== [] || isset($notification->context['exclude_user_ids'])) {
            $notification->context['exclude_user_ids'] = $mergedExcluded;
        }

        OperationsNotificationDiagnostics::write('dispatch_started', $record::class, $record->getKey(), [
            'event_key' => $notification->eventKey,
            'queue_connection' => config('queue.default'),
            'synchronous' => $synchronous,
        ]);
        $recipients = NotificationRecipientService::getQualifiedRecipients(
            $record,
            excludeUserIds: $mergedExcluded,
            context: $notification->context,
        );
        $notification->recordType = $record::class;
        $notification->recordId = $record->getKey();
        $notification->actionUrl ??= static::actionUrl($record);

        if ($recipients->isEmpty()) {
            OperationsNotificationDiagnostics::notification('no_recipients', $notification);

            return 0;
        }

        try {
            if ($synchronous) {
                Notification::sendNow($recipients, $notification);
            } else {
                Notification::send($recipients, $notification);
            }
        } catch (\Throwable $exception) {
            OperationsNotificationDiagnostics::notification('dispatch_failed', $notification, [
                'exception_class' => $exception::class,
            ]);
            throw $exception;
        }

        OperationsNotificationDiagnostics::notification('dispatch_submitted', $notification, [
            'recipient_count' => $recipients->count(),
        ]);

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
        array $context = [],
    ): int {
        return static::send($record, new BaseSystemNotification(
            eventKey: $eventKey,
            customTitle: __($titleKey, $parameters),
            customMessage: __($bodyKey, $parameters),
            icon: $icon,
            color: $color,
            context: $context,
        ), $excludeUserIds, $synchronous);
    }

    public static function resolveActionUrlForRecipient(BaseSystemNotification $notification, mixed $notifiable): ?string
    {
        if (($notification->context['routing_mode'] ?? null) === 'financial_approval' && $notifiable instanceof User) {
            $request = $notification->recordId ? FinancialApprovalRequest::find($notification->recordId) : null;
            if (! $request || ! PayrollApprovalScopeService::canViewRequest($notifiable, $request)) {
                return null;
            }

            try {
                return FinancialApprovalRequestResource::getUrl('index');
            } catch (\Throwable) {
                return null;
            }
        }

        if (($notification->context['routing_mode'] ?? null) !== 'operations_targeted') {
            return $notification->actionUrl;
        }

        if (! $notifiable instanceof User) {
            return $notification->actionUrl;
        }

        if ($notification->recordType === CashHandover::class) {
            if ($notification->eventKey === 'ServiceFinancialHandoverRequested') {
                if ($notifiable->isSuperAdmin()
                    || $notifiable->isOwner()
                    || $notifiable->hasAnyPermission(['confirm_cash_handover', 'view_any_treasury_transaction'])) {
                    try {
                        return TreasuryTransactionResource::getUrl('index');
                    } catch (\Throwable) {
                        return null;
                    }
                }

                return null;
            }

            $transactionId = $notification->context['transaction_id'] ?? null;
            if ($transactionId) {
                $transaction = ServiceTransaction::find($transactionId);
                if ($transaction) {
                    return static::serviceTransactionUrlForUser($transaction, $notifiable);
                }
            }

            return null;
        }

        if ($notification->recordType === ServiceTransaction::class && $notification->recordId) {
            $transaction = ServiceTransaction::find($notification->recordId);
            if ($transaction) {
                return static::serviceTransactionUrlForUser($transaction, $notifiable);
            }

            return null;
        }

        if ($notification->recordType === Invoice::class && $notification->recordId) {
            $invoice = Invoice::find($notification->recordId);

            return $invoice ? static::invoiceUrlForUser($invoice, $notifiable) : null;
        }

        if ($notification->recordType === PayrollRecord::class && $notification->recordId) {
            $payroll = PayrollRecord::find($notification->recordId);

            return $payroll ? static::payrollRecordUrlForUser($payroll, $notifiable) : null;
        }

        return $notification->actionUrl;
    }

    public static function invoiceUrlForUser(Invoice $invoice, User $user): ?string
    {
        try {
            $gate = Gate::forUser($user);

            if ($gate->allows('update', $invoice)) {
                return InvoiceResource::getUrl('edit', ['record' => $invoice]);
            }

            if ($gate->allows('viewAny', Invoice::class)) {
                return InvoiceResource::getUrl('index');
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    public static function payrollRecordUrlForUser(PayrollRecord $payroll, User $user): ?string
    {
        try {
            $gate = Gate::forUser($user);

            if ($gate->allows('update', $payroll)) {
                return PayrollRecordResource::getUrl('edit', ['record' => $payroll]);
            }

            if ($gate->allows('viewAny', PayrollRecord::class)) {
                return PayrollRecordResource::getUrl('index');
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    public static function serviceTransactionUrlForUser(ServiceTransaction $transaction, User $user): ?string
    {
        try {
            if ($user->isSuperAdmin() || $user->isOwner() || $user->hasAnyPermission(['update_service_transaction'])) {
                return ServiceTransactionResource::getUrl('edit', ['record' => $transaction]);
            }

            if ($user->hasAnyPermission(['view_any_service_transaction'])) {
                return ServiceTransactionResource::getUrl('view', ['record' => $transaction]);
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
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
            $record instanceof PayrollRecord => 'PayrollRecords\\PayrollRecord',
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
