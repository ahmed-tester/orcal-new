<?php

namespace App\Services\Notifications;

use App\Models\CashHandover;
use App\Models\ServiceTransaction;
use App\Notifications\BaseSystemNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

class OperationsNotificationDiagnostics
{
    public static function supports(?string $recordType): bool
    {
        return in_array($recordType, [ServiceTransaction::class, CashHandover::class], true);
    }

    public static function write(string $stage, ?string $recordType, int|string|null $recordId, array $context = []): void
    {
        if (! self::supports($recordType)) {
            return;
        }

        try {
            Log::build([
                'driver' => 'daily',
                'path' => storage_path('logs/operations-notifications.log'),
                'level' => 'info',
                'days' => 7,
            ])->info($stage, array_merge($context, [
                'record_type' => $recordType,
                'record_id' => $recordId,
            ]));
        } catch (Throwable) {
            // Diagnostics must never interrupt the financial workflow or delivery.
        }
    }

    public static function notification(string $stage, BaseSystemNotification $notification, array $context = []): void
    {
        self::write($stage, $notification->recordType, $notification->recordId, array_merge($context, [
            'event_key' => $notification->eventKey,
            'notification_id' => $notification->id ?? null,
        ]));
    }
}
