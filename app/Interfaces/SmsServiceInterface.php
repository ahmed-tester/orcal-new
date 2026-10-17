<?php

namespace App\Interfaces;

interface SmsServiceInterface
{
    /**
     * Send an SMS message to a phone number.
     */
    public function sendMessage(string $phoneNumber, string $message): bool;
}
