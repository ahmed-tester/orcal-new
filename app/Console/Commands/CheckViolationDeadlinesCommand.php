<?php

namespace App\Console\Commands;

use App\Enums\ViolationPaymentStatus;
use App\Enums\ViolationStage;
use App\Models\Violation;
use App\Notifications\BaseSystemNotification;
use App\Services\Notifications\NotificationDispatcher;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckViolationDeadlinesCommand extends Command
{
    protected $signature = 'violations:check-deadlines
                            {--days= : Check horizon in days}';

    protected $description = 'Check and send automated alerts for violation legal deadlines, administrative execution, and payment due stages';

    public function handle(): int
    {
        $today = Carbon::today();
        $this->info('Checking active violation deadlines and stages...');

        $unpaidViolations = Violation::with(['company'])
            ->where('payment_status', '!=', ViolationPaymentStatus::PAID->value)
            ->get();

        $alertsCount = 0;

        foreach ($unpaidViolations as $violation) {
            $company = $violation->company;
            $compName = $company?->trade_name ?? '—';
            $vNumber = $violation->violation_number ?? ($violation->external_violation_number ? "#{$violation->external_violation_number}" : "#{$violation->id}");
            $remaining = number_format($violation->remaining_amount, 2).' '.__('violations.transactions.stats.unit');

            $actionUrl = url("/violations/{$violation->id}/edit");

            // 1. Legal Deadline Alert (> 60 days)
            $daysSinceViolation = $violation->violation_date ? Carbon::parse($violation->violation_date)->diffInDays($today, false) : 0;
            $isLegalExceeded = $daysSinceViolation > 60 || ($violation->legal_deadline && Carbon::parse($violation->legal_deadline)->isPast());

            if ($isLegalExceeded || $violation->stage === ViolationStage::LEGAL) {
                $this->sendNotification(
                    title: __('violations.notifications.legal_deadline_warning_title'),
                    body: __('violations.notifications.legal_deadline_warning_body', [
                        'number' => $vNumber,
                        'company' => $compName,
                    ]).' ('.__('violations.violations.fields.remaining_amount').": {$remaining})",
                    actionUrl: $actionUrl,
                    record: $violation,
                    color: 'danger',
                    icon: 'heroicon-o-scale'
                );
                $alertsCount++;

                continue;
            }

            // 2. Administrative Execution Stage Alert
            if ($violation->stage === ViolationStage::ADMIN_EXECUTION) {
                $this->sendNotification(
                    title: __('violations.notifications.admin_execution_title'),
                    body: __('violations.notifications.admin_execution_body', [
                        'number' => $vNumber,
                    ])." - {$compName} (".__('violations.violations.fields.remaining_amount').": {$remaining})",
                    actionUrl: $actionUrl,
                    record: $violation,
                    color: 'danger',
                    icon: 'heroicon-o-shield-exclamation'
                );
                $alertsCount++;

                continue;
            }

            // 3. Suspended / Payment Due Stage Alert
            if ($violation->stage === ViolationStage::SUSPENDED) {
                $this->sendNotification(
                    title: __('violations.notifications.suspended_title'),
                    body: __('violations.notifications.suspended_body', [
                        'number' => $vNumber,
                        'company' => $compName,
                    ]).' ('.__('violations.violations.fields.remaining_amount').": {$remaining})",
                    actionUrl: $actionUrl,
                    record: $violation,
                    color: 'danger',
                    icon: 'heroicon-o-no-symbol'
                );
                $alertsCount++;

                continue;
            }

            // 4. Approaching Stage Deadline Reminder (within 3 days)
            $stageDeadline = match ($violation->stage) {
                ViolationStage::CORRECTION => $violation->correction_deadline ? Carbon::parse($violation->correction_deadline) : null,
                ViolationStage::OBJECTION => $violation->objection_deadline ? Carbon::parse($violation->objection_deadline) : null,
                ViolationStage::GRACE => $violation->grace_deadline ? Carbon::parse($violation->grace_deadline) : null,
                default => null,
            };

            if ($stageDeadline) {
                $daysRemaining = (int) $today->diffInDays($stageDeadline, false);
                if ($daysRemaining >= 0 && $daysRemaining <= 3) {
                    $this->sendNotification(
                        title: __('violations.notifications.stage_expiring_title'),
                        body: __('violations.notifications.stage_expiring_body', [
                            'number' => $vNumber,
                            'stage' => $violation->stage->getLabel(),
                            'days' => $daysRemaining,
                        ]),
                        actionUrl: $actionUrl,
                        record: $violation,
                        color: 'warning',
                        icon: 'heroicon-o-clock'
                    );
                    $alertsCount++;
                }
            }
        }

        $this->info("Completed. {$alertsCount} violation alert(s) processed.");

        return Command::SUCCESS;
    }

    /**
     * Send in-app database notification to target users through Laravel Queue.
     */
    protected function sendNotification(
        string $title,
        string $body,
        ?string $actionUrl = null,
        ?Violation $record = null,
        string $color = 'danger',
        string $icon = 'heroicon-o-bell'
    ): void {
        $notification = new BaseSystemNotification(
            eventKey: 'ViolationDeadlineAlert',
            placeholders: [],
            customMessage: $body,
            customTitle: $title,
            actionUrl: $actionUrl,
            icon: $icon,
            color: $color
        );

        NotificationDispatcher::send($record, $notification);
    }
}
