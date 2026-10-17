<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BaseSystemNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $eventKey,
        public array $placeholders = [],
        public ?string $customMessage = null,
        public ?string $customTitle = null,
        public ?string $actionUrl = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string|class-string>
     */
    public function via(mixed $notifiable): array
    {
        $configuredChannels = config("notifications.events.{$this->eventKey}.channels")
            ?? config('notifications.default_channels', ['database']);

        $channels = [];

        foreach ($configuredChannels as $channel) {
            match ($channel) {
                'database', 'db' => $channels[] = 'database',
                'mail', 'email'  => $channels[] = 'mail',
                'telegram'       => $channels[] = TelegramChannel::class,
                'sms'            => $channels[] = SmsChannel::class,
                default          => null,
            };
        }

        return array_unique($channels);
    }

    /**
     * Database notification payload (Filament Bell Compatible).
     */
    public function toDatabase(mixed $notifiable): array
    {
        $titleTemplate = $this->getLocalizedConfig('database.title') ?? 'إشعار جديد في النظام';
        $bodyTemplate = $this->customMessage
            ?? $this->getLocalizedConfig('database.body')
            ?? 'لديك إشعار جديد في النظام.';

        $title = $this->customTitle ?? $this->renderString($titleTemplate, $this->placeholders);
        $body = $this->renderString($bodyTemplate, $this->placeholders);

        $actions = [];
        if ($this->actionUrl) {
            $actions[] = [
                'name' => 'view',
                'label' => 'عرض التفاصيل',
                'url' => $this->actionUrl,
                'shouldOpenInNewTab' => false,
                'shouldMarkAsRead' => true,
            ];
        }

        return [
            'title' => $title,
            'body' => $body,
            'icon' => 'heroicon-o-bell',
            'iconColor' => 'primary',
            'color' => 'primary',
            'duration' => 5000,
            'actions' => $actions,
            'format' => 'filament',
            'event_key' => $this->eventKey,
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
     * Telegram notification payload.
     */
    public function toTelegram(mixed $notifiable): ?string
    {
        if ($this->customMessage) {
            return $this->renderString($this->customMessage, $this->placeholders);
        }

        $template = $this->getLocalizedConfig('telegram');
        if (!$template) {
            return null;
        }

        return $this->renderString($template, $this->placeholders);
    }



    /**
     * SMS notification payload.
     */
    public function toSms(mixed $notifiable): ?string
    {
        if ($this->customMessage) {
            return $this->renderString($this->customMessage, $this->placeholders);
        }

        $template = $this->getLocalizedConfig('sms');
        if (!$template) {
            return null;
        }

        return $this->renderString($template, $this->placeholders);
    }

    /**
     * Render template string by substituting placeholders.
     */
    public function renderString(string $template, array $placeholders): string
    {
        $rendered = $template;
        foreach ($placeholders as $key => $value) {
            $rendered = str_replace('{' . $key . '}', (string) ($value ?? ''), $rendered);
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

        return is_string($config) ? $config : null;
    }
}
