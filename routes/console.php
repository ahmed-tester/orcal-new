<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use App\Jobs\SyncDailyAttendanceToPayrollJob;
use Illuminate\Support\Facades\Schedule;

Schedule::command('attendance:mark-absent')->everyFiveMinutes();
Schedule::command('iqama:check-expiry')->dailyAt('09:00')->withoutOverlapping();
Schedule::command('company:check-license-expiry')->dailyAt('09:15')->withoutOverlapping();
Schedule::command('leaves:check-overdue')->dailyAt('09:30')->withoutOverlapping();
Schedule::command('violations:check-deadlines')->dailyAt('09:45')->withoutOverlapping();
Schedule::command('debts:check-due-dates')->dailyAt('10:00')->withoutOverlapping();
Schedule::command('service-transactions:check-overdue')->dailyAt('10:15')->withoutOverlapping();
Schedule::command('performance:check-escalations')->hourly()->withoutOverlapping();
Schedule::command('mail:sync-emails')->everyMinute()->withoutOverlapping();
Schedule::command('mail:check-escalation')->everyFifteenMinutes()->withoutOverlapping();
Schedule::job(new SyncDailyAttendanceToPayrollJob)->dailyAt('23:30');
