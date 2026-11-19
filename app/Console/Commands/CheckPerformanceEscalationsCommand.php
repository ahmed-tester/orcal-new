<?php

namespace App\Console\Commands;

use App\Services\PerformanceEscalationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class CheckPerformanceEscalationsCommand extends Command
{
    protected $signature = 'performance:check-escalations';

    protected $description = 'Evaluate department performance scores and open or close escalation cases';

    public function handle(PerformanceEscalationService $service): int
    {
        $summary = $service->evaluateAll();

        $this->line(__('performance_escalations.commands.opened', ['count' => $summary['opened']]));
        $this->line(__('performance_escalations.commands.closed', ['count' => $summary['closed']]));
        $this->line(__('performance_escalations.commands.notified', ['count' => $summary['notified']]));

        if ($summary['failed_notifications'] > 0) {
            $this->warn(__('performance_escalations.commands.failed_notifications', ['count' => $summary['failed_notifications']]));
        }

        $this->info(__('performance_escalations.commands.summary', [
            'companies' => $summary['companies'],
            'departments' => $summary['departments'],
        ]));

        return Command::SUCCESS;
    }
}
