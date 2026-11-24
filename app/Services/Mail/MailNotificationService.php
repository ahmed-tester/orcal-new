<?php

namespace App\Services\Mail;

use App\Enums\Mail\MailMessageStatus;
use App\Enums\Mail\MailPriority;
use App\Models\Mail\MailAccount;
use App\Models\Mail\MailMessage;
use App\Models\Setting;
use App\Models\UnifiedAuditLog;
use App\Models\User;
use App\Notifications\BaseSystemNotification;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class MailNotificationService
{
    /**
     * Notify eligible assigned users and super admins about a new important or under-review email.
     */
    public function sendNewEmailNotification(MailMessage $email): int
    {
        $titleKey = $email->is_important
            ? 'mail_inbox.notifications.new_important_title'
            : 'mail_inbox.notifications.new_review_title';

        $parameters = [
            'sender' => $email->sender_email,
            'subject' => $email->subject,
            'priority' => $email->priority?->getLabel() ?? '',
            'category' => $email->categoryLabel(),
        ];

        $sentCount = NotificationDispatcher::translated(
            record: $email,
            eventKey: 'mail.new_message',
            titleKey: $titleKey,
            bodyKey: 'mail_inbox.notifications.new_body',
            parameters: $parameters,
            icon: $email->is_important ? 'heroicon-o-envelope' : 'heroicon-o-exclamation-triangle',
            color: $email->is_important ? 'success' : 'warning',
            synchronous: true,
        );

        $emailEnabled = filter_var(Setting::get('mail_enable_email_notifications', false), FILTER_VALIDATE_BOOLEAN);
        $notifyEmail = Setting::get('mail_notification_email');

        if ($emailEnabled && ! empty($notifyEmail)) {
            try {
                $title = (string) __($titleKey, $parameters);
                $body = (string) __('mail_inbox.notifications.new_body', $parameters);
                Mail::raw("{$title}\n{$body}", function ($msg) use ($notifyEmail, $title) {
                    $msg->to($notifyEmail)->subject($title);
                });
            } catch (\Throwable $e) {
                Log::warning('MailNotificationService: failed sending external email alert: '.$e->getMessage());
            }
        }

        return $sentCount;
    }

    /**
     * Check high-priority unanswered emails past threshold and trigger escalation to Super Admins.
     */
    public function checkEscalations(): int
    {
        $escalationHours = (int) Setting::get('mail_escalation_hours', 4);
        $threshold = now()->subHours($escalationHours);

        $emailsToEscalate = MailMessage::query()
            ->where('priority', MailPriority::HIGH->value)
            ->where('is_important', true)
            ->whereNull('replied_at')
            ->whereNull('escalated_at')
            ->where('status', '!=', MailMessageStatus::REJECTED->value)
            ->where('status', '!=', MailMessageStatus::REPLIED->value)
            ->where('received_at', '<=', $threshold)
            ->get();

        $count = 0;

        foreach ($emailsToEscalate as $email) {
            $email->update(['escalated_at' => now()]);
            $count++;

            $parameters = [
                'sender' => $email->sender_email,
                'subject' => $email->subject,
                'hours' => $escalationHours,
            ];

            $notification = new BaseSystemNotification(
                eventKey: 'mail.escalation',
                customTitle: (string) __('mail_inbox.notifications.escalation_title'),
                customMessage: (string) __('mail_inbox.notifications.escalation_body', $parameters),
                recordType: MailMessage::class,
                recordId: $email->id,
                actionUrl: NotificationDispatcher::actionUrl($email),
                icon: 'heroicon-o-clock',
                color: 'danger',
            );

            $superAdmins = User::query()
                ->where('status', 'active')
                ->get()
                ->filter(fn (User $user) => MailAccessService::canAdministerAllMail($user));

            foreach ($superAdmins as $admin) {
                $admin->notifyNow($notification);
            }

            if (Schema::hasTable('unified_audit_logs')) {
                UnifiedAuditLog::create([
                    'company_id' => $email->company_id,
                    'auditable_type' => MailMessage::class,
                    'auditable_id' => $email->id,
                    'action_type' => 'mail_escalated',
                    'payload' => [
                        'hours_delayed' => $escalationHours,
                        'subject' => $email->subject,
                    ],
                    'created_at' => now(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Notify Super Admins about a persistent mail sync error.
     */
    public function notifySyncError(MailAccount $account, string $errorMessage): int
    {
        return NotificationDispatcher::translated(
            record: $account,
            eventKey: 'mail.sync_error',
            titleKey: 'mail_inbox.notifications.sync_error_title',
            bodyKey: 'mail_inbox.notifications.sync_error_body',
            parameters: [
                'email' => $account->email,
                'name' => $account->name,
                'error' => $errorMessage,
            ],
            icon: 'heroicon-o-exclamation-circle',
            color: 'danger',
            synchronous: true,
        );
    }

    /**
     * Delete database notifications tied to a mail account and all of its messages.
     */
    public function deleteAccountNotifications(MailAccount $account): int
    {
        $messageIds = $account->messages()->pluck('id')->all();

        return $this->deleteNotificationsForRecords(MailAccount::class, [$account->id])
            + $this->deleteNotificationsForRecords(MailMessage::class, $messageIds);
    }

    /**
     * Delete database notifications matching a record type and list of record IDs.
     *
     * @param  array<int, int|string>  $recordIds
     */
    public function deleteNotificationsForRecords(string $recordType, array $recordIds): int
    {
        $ids = array_values(array_unique(array_filter(
            $recordIds,
            static fn ($id): bool => $id !== null && $id !== ''
        )));

        if ($ids === [] || ! Schema::hasTable('notifications')) {
            return 0;
        }

        $deleted = 0;

        foreach (array_chunk($ids, 500) as $chunk) {
            $values = array_values(array_unique(array_merge(
                $chunk,
                array_map(static fn ($id): string => (string) $id, $chunk)
            )));

            $deleted += DatabaseNotification::query()
                ->where('data->record_type', $recordType)
                ->whereIn('data->record_id', $values)
                ->delete();
        }

        return $deleted;
    }
}
