<?php

namespace App\Console\Commands\Mail;

use App\Models\Mail\MailAccount;
use App\Services\Mail\MailSyncService;
use Illuminate\Console\Command;

class SyncEmailsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:sync-emails {account? : ID of specific account to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync active mail accounts, fetch new messages from INBOX and Spam/Junk';

    public function handle(MailSyncService $syncService): int
    {
        $accountId = $this->argument('account');

        $query = MailAccount::where('is_active', true);
        if ($accountId) {
            $query->where('id', $accountId);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->info((string) __('mail_inbox.sync.command_no_accounts'));

            return self::SUCCESS;
        }

        foreach ($accounts as $account) {
            $this->info((string) __('mail_inbox.sync.command_syncing', ['email' => $account->email]));
            $res = $syncService->syncAccount($account);
            $this->info((string) __('mail_inbox.sync.command_result', [
                'email' => $account->email,
                'found' => $res['found'],
                'imported' => $res['imported'],
                'rejected' => $res['rejected'],
            ]));
        }

        return self::SUCCESS;
    }
}
