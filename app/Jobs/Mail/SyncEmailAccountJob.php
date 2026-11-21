<?php

namespace App\Jobs\Mail;

use App\Models\Mail\MailAccount;
use App\Services\Mail\MailSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncEmailAccountJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(
        public MailAccount $account
    ) {}

    public function handle(MailSyncService $syncService): void
    {
        if (! $this->account->is_active) {
            return;
        }

        $syncService->syncAccount($this->account);
    }
}
