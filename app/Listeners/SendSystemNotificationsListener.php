<?php

namespace App\Listeners;

use App\Interfaces\NotificationEventInterface;
use App\Notifications\BaseSystemNotification;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Support\Facades\Log;

class SendSystemNotificationsListener
{
    public function handle(NotificationEventInterface $event): void
    {
        if (! $record = $event->getRecord()) {
            Log::warning('Notification skipped: missing record.', ['event_key' => $event->getEventKey()]);

            return;
        }

        NotificationDispatcher::send($record, new BaseSystemNotification(
            eventKey: $event->getEventKey(),
            placeholders: $event->getPlaceholders(),
            customMessage: $event->getCustomMessage(),
            actionUrl: $event->getActionUrl(),
        ));
    }
}
