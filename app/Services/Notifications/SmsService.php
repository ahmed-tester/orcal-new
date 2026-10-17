<?php

namespace App\Services\Notifications;

use App\Interfaces\SmsServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService implements SmsServiceInterface
{
    public function sendMessage(string $phoneNumber, string $message): bool
    {
        $apiUrl = config('services.sms.api_url');
        $apiKey = config('services.sms.api_key');
        $sender = config('services.sms.sender_name', 'ORCAL');

        if (empty($apiUrl) || empty($apiKey)) {
            Log::info("[SMS Notification (Mock)] To: {$phoneNumber} | Sender: {$sender} | Message: {$message}");
            return true;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(10)->post($apiUrl, [
                'recipient' => $phoneNumber,
                'sender' => $sender,
                'body' => $message,
            ]);

            if (!$response->successful()) {
                Log::error("[SMS Notification Failed] Phone: {$phoneNumber}, Response: " . $response->body());
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error("[SMS Notification Exception] Error: " . $e->getMessage());
            return false;
        }
    }
}
