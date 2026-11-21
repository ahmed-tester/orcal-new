<?php

namespace App\Console\Commands\Mail;

use App\Jobs\Mail\CheckMailEscalationJob;
use Illuminate\Console\Command;

class CheckMailEscalationCommand extends Command
{
    protected $signature = 'mail:check-escalation';

    protected $description = 'Check high-priority unanswered emails and dispatch escalation alerts';

    public function handle(): int
    {
        CheckMailEscalationJob::dispatch();
        $this->info((string) __('mail_inbox.notifications.escalation_dispatched'));

        return self::SUCCESS;
    }
}
