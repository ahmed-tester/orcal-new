<?php

namespace App\Channels;

use App\Interfaces\SmsServiceInterface;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    public function __construct(
        protected SmsServiceInterface $sms
    ) {}

    public function send(mixed $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);
        if (empty($message)) {
            Log::debug("[SmsChannel] Empty message generated. Skipping SMS send.");
            return;
        }

        $phone = null;
        if (method_exists($notifiable, 'routeNotificationFor')) {
            $phone = $notifiable->routeNotificationFor('sms', $notification);
        }

        $phone = $phone
            ?? $notifiable->phone
            ?? $notifiable->user?->phone
            ?? $notifiable->detail?->phone
            ?? null;

        if (!$phone) {
            Log::warning("[SmsChannel] ⚠️ No phone number found for notifiable. Skipping SMS notification.");
            return;
        }

        Log::info("[SmsChannel] 📤 Sending SMS notification to Phone: {$phone}");
        $this->sms->sendMessage((string) $phone, (string) $message);
    }
}
