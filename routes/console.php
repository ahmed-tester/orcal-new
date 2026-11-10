<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('attendance:mark-absent')->everyFiveMinutes();
Schedule::command('iqama:check-expiry')->dailyAt('09:00')->withoutOverlapping();
Schedule::command('company:check-license-expiry')->dailyAt('09:15')->withoutOverlapping();
Schedule::command('leaves:check-overdue')->dailyAt('09:30')->withoutOverlapping();
Schedule::command('violations:check-deadlines')->dailyAt('09:45')->withoutOverlapping();
Schedule::command('debts:check-due-dates')->dailyAt('10:00')->withoutOverlapping();
Schedule::job(new \App\Jobs\SyncDailyAttendanceToPayrollJob())->dailyAt('23:30');
