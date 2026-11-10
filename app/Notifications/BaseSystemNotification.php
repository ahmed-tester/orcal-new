<?php

namespace App\Notifications;

use App\Models\User;
use App\Services\Notifications\NotificationRecipientService;
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

    public function __construct(
        public string $eventKey,
        public array $placeholders = [],
        public ?string $customMessage = null,
        public ?string $customTitle = null,
        public ?string $actionUrl = null,
        public ?string $recordType = null,
        public int|string|null $recordId = null,
        public ?string $icon = 'heroicon-o-bell',
        public ?string $color = 'primary'
    ) {
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
        // Filter inactive accounts before dispatch; shouldSend rechecks at delivery.
        if ($notifiable instanceof User && $notifiable->status !== 'active') {
            return [];
        }

        return ['database'];
    }

    public function shouldSend(mixed $notifiable, string $channel): bool
    {
        if ($channel !== 'database' || ! $notifiable instanceof User || $notifiable->status !== 'active'
            || ! $this->recordType || ! is_subclass_of($this->recordType, Model::class)) {
            return false;
        }

        $record = $this->recordType::query()->find($this->recordId);

        return $record !== null && NotificationRecipientService::isUserEligibleToReceive(
            $notifiable,
            $record,
            NotificationRecipientService::resolveDefaultUpdatePermissions($record),
            NotificationRecipientService::resolveCompanyId($record),
            NotificationRecipientService::resolveBranchId($record),
        );
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

        $actions = [];
        if ($this->actionUrl) {
            $actions[] = [
                'name' => 'view',
                'label' => __('notifications.general.view_details'),
                'url' => $this->actionUrl,
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
            'duration' => 5000,
            'actions' => $actions,
            'format' => 'filament',
            'event_key' => $this->eventKey,
            'record_type' => $this->recordType,
            'record_id' => $this->recordId,
            'action_url' => $this->actionUrl,
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
