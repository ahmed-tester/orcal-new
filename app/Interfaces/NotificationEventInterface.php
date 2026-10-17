<?php

namespace App\Interfaces;

interface NotificationEventInterface
{
    /**
     * Get the recipient(s) who should receive this notification.
     * Can return a single Notifiable model, a Collection, or an array of models.
     */
    public function getNotifiable(): mixed;

    /**
     * Get the unique event key that matches the configuration in config/notifications.php.
     */
    public function getEventKey(): string;

    /**
     * Get dynamic placeholders/variables to replace in notification templates.
     * Example: ['order_id' => 1050, 'employee_name' => 'سالم']
     *
     * @return array<string, mixed>
     */
    public function getPlaceholders(): array;

    /**
     * Get the custom message text if provided directly by the user/form.
     * If null, the system will fall back to the default template from config/notifications.php.
     */
    public function getCustomMessage(): ?string;
}
