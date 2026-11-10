<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Model;

interface NotificationEventInterface
{
    /**
     * Get the target entity/record associated with this notification.
     */
    public function getRecord(): ?Model;

    /**
     * Get the unique event key that matches the configuration in config/notifications.php.
     */
    public function getEventKey(): string;

    public function getPlaceholders(): array;

    /**
     * Get the custom message text if provided directly by the user/form.
     */
    public function getCustomMessage(): ?string;

    /**
     * Get the primary action URL to review the record in Filament.
     */
    public function getActionUrl(): ?string;

    /**
     * Legacy recipient resolver (backward compatibility).
     */
    public function getNotifiable(): mixed;
}
