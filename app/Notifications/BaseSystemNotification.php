<?php

namespace App\Notifications;

use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\NotificationRecipientService;
use App\Services\Notifications\OperationsNotificationDiagnostics;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

class BaseSystemNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * Backoff delay between retry attempts in seconds.
     */
    public int $backoff = 10;

    public string $eventKey;

    public array $placeholders = [];

    public ?string $customMessage = null;

    public ?string $customTitle = null;

    public ?string $actionUrl = null;

    public ?string $recordType = null;

    public int|string|null $recordId = null;

    public ?string $icon = 'heroicon-o-bell';

    public ?string $color = 'primary';

    public array $context = [];

    public function __construct(
        string $eventKey,
        array $placeholders = [],
        ?string $customMessage = null,
        ?string $customTitle = null,
        ?string $actionUrl = null,
        ?string $recordType = null,
        int|string|null $recordId = null,
        ?string $icon = 'heroicon-o-bell',
        ?string $color = 'primary',
        array $context = [],
    ) {
        $this->eventKey = $eventKey;
        $this->placeholders = $placeholders;
        $this->customMessage = $customMessage;
        $this->customTitle = $customTitle;
        $this->actionUrl = $actionUrl;
        $this->recordType = $recordType;
        $this->recordId = $recordId;
        $this->icon = $icon;
        $this->color = $color;
        $this->context = $context;
        $this->afterCommit = true;
    }

    /**
     * Get the notification's delivery channels.
     * Centralized guard: Database channel only.
     *
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        OperationsNotificationDiagnostics::notification('delivery_channels_resolved', $this, [
            'recipient_id' => $notifiable instanceof User ? $notifiable->id : null,
            'active' => $notifiable instanceof User && $notifiable->status === 'active',
        ]);

        return $notifiable instanceof User && $notifiable->status === 'active' ? ['database'] : [];
    }

    public function shouldSend(mixed $notifiable, string $channel): bool
    {
        OperationsNotificationDiagnostics::notification('delivery_started', $this, [
            'recipient_id' => $notifiable instanceof User ? $notifiable->id : null,
            'channel' => $channel,
        ]);
        if ($channel !== 'database' || ! $notifiable instanceof User || $notifiable->status !== 'active'
            || ! $this->recordType || ! is_subclass_of($this->recordType, Model::class)) {
            OperationsNotificationDiagnostics::notification('delivery_denied', $this, ['reason' => 'invalid_channel_recipient_or_record_type']);

            return false;
        }

        $record = $this->recordType::query()->find($this->recordId);

        $reason = $record === null ? 'record_missing' : null;
        $allowed = $record !== null && NotificationRecipientService::isUserEligibleToReceive(
            $notifiable,
            $record,
            NotificationRecipientService::resolveDefaultUpdatePermissions($record),
            NotificationRecipientService::resolveCompanyId($record),
            NotificationRecipientService::resolveBranchId($record),
            $reason,
            $this->context,
        );
        OperationsNotificationDiagnostics::notification($allowed ? 'delivery_allowed' : 'delivery_denied', $this, [
            'recipient_id' => $notifiable->id,
            'reason' => $reason,
        ]);

        return $allowed;
    }

    public function failed(\Throwable $exception): void
    {
        OperationsNotificationDiagnostics::notification('delivery_failed', $this, [
            'exception_class' => $exception::class,
        ]);
    }

    /**
     * Database notification payload (Filament Bell Compatible).
     */
    public function toDatabase(mixed $notifiable): array
    {
        $titleTemplate = $this->getLocalizedConfig('database.title') ?? __('notifications.general.title');
        $bodyTemplate = $this->customMessage
            ?? $this->getLocalizedConfig('database.body')
            ?? __('notifications.general.body');

        $title = $this->customTitle ?? $this->renderString($titleTemplate, $this->placeholders);
        $body = $this->renderString($bodyTemplate, $this->placeholders);

        $resolvedUrl = NotificationDispatcher::resolveActionUrlForRecipient($this, $notifiable);

        $actions = [];
        if ($resolvedUrl) {
            $actions[] = [
                'name' => 'view',
                'label' => __('notifications.general.view_details'),
                'url' => $resolvedUrl,
                'shouldOpenInNewTab' => false,
                'shouldMarkAsRead' => true,
            ];
        }

        return [
            'title' => $title,
            'body' => $body,
            'icon' => $this->icon ?: 'heroicon-o-bell',
            'iconColor' => $this->color ?: 'primary',
            'color' => $this->color ?: 'primary',
            'duration' => 'persistent',
            'actions' => $actions,
            'format' => 'filament',
            'event_key' => $this->eventKey,
            'record_type' => $this->recordType,
            'record_id' => $this->recordId,
            'action_url' => $resolvedUrl,
            'placeholders' => $this->placeholders,
            'created_at' => now()->toIso8601String(),
        ];
    }

    public function toArray(mixed $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * Render template string by substituting placeholders.
     */
    public function renderString(string $template, array $placeholders): string
    {
        $rendered = $template;
        foreach ($placeholders as $key => $value) {
            $rendered = str_replace('{'.$key.'}', (string) ($value ?? ''), $rendered);
        }

        return $rendered;
    }

    /**
     * Retrieve localized configuration for the current event.
     */
    protected function getLocalizedConfig(string $keyPath): ?string
    {
        $locale = app()->getLocale() ?: 'ar';
        $path = "notifications.events.{$this->eventKey}.{$keyPath}";

        $config = config("{$path}.{$locale}")
            ?? config("{$path}.ar")
            ?? config("{$path}.en")
            ?? config($path);

        return is_string($config) ? __($config) : null;
    }
}
