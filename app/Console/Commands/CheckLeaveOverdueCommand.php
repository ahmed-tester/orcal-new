<?php

namespace App\Console\Commands;

use App\Enums\LeaveStatus;
use App\Events\LeaveOverdueAlertEvent;
use App\Events\LeaveReturnUpcomingAlertEvent;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckLeaveOverdueCommand extends Command
{
    protected $signature = 'leaves:check-overdue
                            {--days-upcoming=3 : Threshold days for upcoming return alert}';

    protected $description = 'Check approved leaves for overdue return or upcoming return and dispatch notifications';

    public function handle(): int
    {
        $today = Carbon::today();
        $upcomingThreshold = (int) $this->option('days-upcoming');

        $this->info(__('notifications.commands.checking_leaves'));

        $approvedLeaves = Leave::with(['employee.user', 'employee.company', 'leaveType'])
            ->where('status', LeaveStatus::APPROVED)
            ->whereNull('actual_return_date')
            ->get();

        $overdueCount = 0;
        $upcomingCount = 0;

        foreach ($approvedLeaves as $leave) {
            $employee = $leave->employee;
            if (!$employee) {
                continue;
            }

            $endDate = Carbon::parse($leave->end_date);

            if ($today->greaterThan($endDate)) {
                // Overdue return
                $daysOverdue = (int) $endDate->diffInDays($today);
                $empName = $employee->user?->name ?? ('#' . $employee->id);

                $this->line("🚨 <error>[OVERDUE]</error> " . __('notifications.leaves.overdue_console', ['employee' => $empName, 'days' => $daysOverdue]));

                event(new LeaveOverdueAlertEvent(
                    employee: $employee,
                    leave: $leave,
                    daysOverdue: $daysOverdue
                ));

                $overdueCount++;
            } elseif ($today->lessThanOrEqualTo($endDate)) {
                // Upcoming return check
                $daysRemaining = (int) $today->diffInDays($endDate);

                if ($daysRemaining <= $upcomingThreshold) {
                    $empName = $employee->user?->name ?? ('#' . $employee->id);
                    $this->line("✈️ <comment>[UPCOMING RETURN]</comment> " . __('notifications.leaves.upcoming_console', ['employee' => $empName, 'days' => $daysRemaining]));

                    event(new LeaveReturnUpcomingAlertEvent(
                        employee: $employee,
                        leave: $leave,
                        daysRemaining: $daysRemaining
                    ));

                    $upcomingCount++;
                }
            }
        }

        $this->info(__('notifications.commands.leaves_done', ['overdue' => $overdueCount, 'upcoming' => $upcomingCount]));
        Log::info("[Leaves Check Command] Processed {$overdueCount} overdue and {$upcomingCount} upcoming return alert(s).");

        return Command::SUCCESS;
    }
}
