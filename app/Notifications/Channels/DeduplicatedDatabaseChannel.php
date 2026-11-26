<?php

namespace App\Notifications\Channels;

use App\Notifications\BaseSystemNotification;
use App\Services\Notifications\OperationsNotificationDiagnostics;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Notifications\Channels\DatabaseChannel;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notification;

class DeduplicatedDatabaseChannel extends DatabaseChannel
{
    /**
     * Send the given notification, enforcing atomic database uniqueness when a dedup_key is present.
     */
    public function send($notifiable, Notification $notification): ?DatabaseNotification
    {
        $payload = $this->buildPayload($notifiable, $notification);
        $dedupKey = property_exists($notification, 'context') && is_array($notification->context)
            ? ($notification->context['dedup_key'] ?? null)
            : null;

        if (is_string($dedupKey) && $dedupKey !== '') {
            $payload['dedup_key_hash'] = hash('sha256', $dedupKey);
        }

        try {
            return $notifiable->routeNotificationFor('database', $notification)->create($payload);
        } catch (UniqueConstraintViolationException $e) {
            if (! empty($payload['dedup_key_hash'])) {
                if ($notification instanceof BaseSystemNotification) {
                    OperationsNotificationDiagnostics::notification('delivery_denied', $notification, [
                        'recipient_id' => $notifiable->getKey(),
                        'reason' => 'duplicate_notification_prevented',
                    ]);
                }

                return null;
            }

            throw $e;
        }
    }
}
