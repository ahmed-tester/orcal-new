<?php

namespace App\Services\Notifications;

use App\Interfaces\SmsServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService implements SmsServiceInterface
{
    /**
     * Send an SMS message through the configured provider or mock driver.
     */
    public function sendMessage(string $phoneNumber, string $message): bool
    {
        // 1. Central disable guard: Even if called directly, return false when disabled
        if (! config('notifications.sms.enabled', false)) {
            Log::debug('[SmsService] SMS delivery attempted while notifications.sms.enabled is false. Aborting.');

            return false;
        }

        $sanitizedPhone = $this->sanitizePhoneNumber($phoneNumber);
        if (! $sanitizedPhone) {
            Log::warning("[SmsService] Invalid phone number provided: '{$phoneNumber}'.");

            return false;
        }

        $provider = config('notifications.sms.provider', 'mock');
        $sender = config('notifications.sms.sender_name')
            ?: config('services.sms.sender_name', 'ORCAL');

        // 2. Mock provider for testing environments
        if ($provider === 'mock') {
            Log::info("[SMS Notification (Mock)] To: {$sanitizedPhone} | Sender: {$sender} | Message: {$message}");

            return true;
        }

        // 3. Real provider config check
        $apiUrl = config('notifications.sms.api_url') ?: config('services.sms.api_url');
        $apiKey = config('notifications.sms.api_key') ?: config('services.sms.api_key');

        if (empty($apiUrl) || empty($apiKey)) {
            Log::warning("[SmsService] Missing SMS credentials (api_url / api_key). Cannot deliver SMS to {$sanitizedPhone}.");

            return false;
        }

        // 4. HTTP call with safe error handling
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(10)->post($apiUrl, [
                'recipient' => $sanitizedPhone,
                'sender' => $sender,
                'body' => $message,
            ]);

            if (! $response->successful()) {
                Log::error("[SMS Notification Failed] Phone: {$sanitizedPhone}, Status: {$response->status()}, Response: ".$response->body());

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('[SMS Notification Exception] Error: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Sanitize phone number (strip spaces/symbols, validate minimum length).
     */
    public function sanitizePhoneNumber(string $phone): ?string
    {
        $cleaned = preg_replace('/[^\d+]/', '', trim($phone));

        if (empty($cleaned) || strlen($cleaned) < 8) {
            return null;
        }

        return $cleaned;
    }
}
