<?php

namespace App\Jobs\Mail;

use App\Models\Mail\MailAccount;
use App\Services\Mail\MailSyncService;
use App\Services\Mail\MicrosoftMailOAuthService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncEmailAccountJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(
        public MailAccount $account,
        public bool $verifyOnly = false,
    ) {}

    public function handle(MailSyncService $syncService): void
    {
        try {
            $account = $this->account->fresh();
            if (! $account) {
                return;
            }

            if ($this->verifyOnly) {
                if ($account->hasOAuthTokens()) {
                    $result = $syncService->testConnection($account);
                    if (! $result['success']) {
                        $account->update([
                            'status' => \App\Enums\Mail\MailAccountStatus::CONNECTION_ERROR,
                            'error_message' => $result['message'],
                        ]);
                    }
                }

                return;
            }

            if (! $account->isReadyForSync()) {
                return;
            }

            $syncService->syncAccount($account);
        } finally {
            MicrosoftMailOAuthService::clearVerifying($this->account->id);
        }
    }
}
