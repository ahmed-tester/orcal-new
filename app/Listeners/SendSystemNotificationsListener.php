<?php

namespace App\Listeners;

use App\Interfaces\NotificationEventInterface;
use App\Notifications\BaseSystemNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SendSystemNotificationsListener
{
    /**
     * Handle the incoming notification event.
     */
    public function handle(NotificationEventInterface $event): void
    {
        $eventKey = $event->getEventKey();
        $requiredPermissions = config("notifications.events.{$eventKey}.required_permissions", []);

        Log::info("[Notification System] 🔔 Handling event: [{$eventKey}]");

        // 1. Fetch authorized users who have permission to edit/update this model
        $permittedUsers = collect();
        if (!empty($requiredPermissions)) {
            try {
                $permittedUsers = \App\Models\User::permission($requiredPermissions)->get();
                Log::info("[Notification System] 🔑 Resolved " . $permittedUsers->count() . " user(s) with permission(s) [" . implode(', ', $requiredPermissions) . "] for event [{$eventKey}]");
            } catch (\Throwable $e) {
                Log::error("[Notification System] Error finding users with permissions: " . $e->getMessage());
            }
        }

        // 2. Fetch any explicit primary individual target (like employee for their own alert or custom recipient)
        $rawNotifiables = $event->getNotifiable();
        $primaryRecipients = match (true) {
            $rawNotifiables instanceof Collection => $rawNotifiables,
            is_array($rawNotifiables)             => collect($rawNotifiables),
            !empty($rawNotifiables)               => collect([$rawNotifiables]),
            default                               => collect(),
        };

        // Combine: permitted users for this model + primary individual target
        $recipients = $permittedUsers->concat($primaryRecipients)->filter();

        if ($recipients->isEmpty()) {
            Log::warning("[Notification System] ⚠️ No permitted or notifiable recipients found for event [{$eventKey}]");
            return;
        }

        Log::info("[Notification System] 👥 Total " . $recipients->count() . " recipient(s) to notify for [{$eventKey}]");

        $notification = new BaseSystemNotification(
            eventKey: $eventKey,
            placeholders: $event->getPlaceholders(),
            customMessage: $event->getCustomMessage()
        );

        $notifiedIds = [];

        foreach ($recipients as $notifiable) {
            if (!$notifiable) {
                continue;
            }

            if ($notifiable instanceof \App\Models\Employee && $notifiable->user) {
                $notifiable = $notifiable->user;
            }

            $userKey = method_exists($notifiable, 'getKey') && $notifiable->getKey()
                ? (get_class($notifiable) . ':' . $notifiable->getKey())
                : (is_object($notifiable) ? spl_object_hash($notifiable) : (string) $notifiable);

            if (in_array($userKey, $notifiedIds)) {
                continue; // Prevent duplicate notifications
            }

            $notifiedIds[] = $userKey;

            // Also track underlying user_id if this is an Employee model
            if (isset($notifiable->user_id) && $notifiable->user_id) {
                $notifiedIds[] = 'App\Models\User:' . $notifiable->user_id;
            }

            $recipientIdentifier = method_exists($notifiable, 'getKey') 
                ? (get_class($notifiable) . ' #' . $notifiable->getKey() . ' (' . ($notifiable->name ?? '') . ')')
                : (is_object($notifiable) ? get_class($notifiable) : (string) $notifiable);

            if (method_exists($notifiable, 'notify')) {
                Log::info("[Notification System] 🚀 Dispatching notification to: {$recipientIdentifier}");
                $notifiable->notify($notification);
            } else {
                Log::warning("[Notification System] ⚠️ Recipient {$recipientIdentifier} does not use Notifiable trait.");
            }
        }
    }
}
