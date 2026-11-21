<?php

namespace App\Jobs\Mail;

use App\Services\Mail\MailNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckMailEscalationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function handle(MailNotificationService $notificationService): void
    {
        $notificationService->checkEscalations();
    }
}
