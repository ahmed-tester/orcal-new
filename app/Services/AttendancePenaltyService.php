<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use Carbon\Carbon;

class AttendancePenaltyService
{
    /**
     * Get branch attendance settings with safe defaults.
     */
    public static function getBranchSettings(?Branch $branch): array
    {
        $settings = $branch?->attendance_settings ?? [];

        return [
            'check_in_time'                => $settings['check_in_time'] ?? '08:00',
            'check_out_time'               => $settings['check_out_time'] ?? '16:00',
            'late_deduction_enabled'       => (bool) ($settings['late_deduction_enabled'] ?? false),
            'late_grace_minutes'           => (int) ($settings['late_grace_minutes'] ?? $settings['late_tolerance_minutes'] ?? 15),
            'late_quarter_day_minutes'     => (int) ($settings['late_quarter_day_minutes'] ?? 30),
            'late_half_day_minutes'        => (int) ($settings['late_half_day_minutes'] ?? 60),
            'absence_penalty_enabled'      => (bool) ($settings['absence_penalty_enabled'] ?? false),
            'absence_deduction_multiplier' => (float) ($settings['absence_deduction_multiplier'] ?? 1.0),
        ];
    }

    /**
     * Calculate late deduction in fraction of days (0.0, 0.25, 0.50, 1.00) for a single attendance record.
     */
    public static function calculateLateDeductionDays(int $lateMinutes, ?Branch $branch = null): float
    {
        if ($lateMinutes <= 0) {
            return 0.0;
        }

        $config = static::getBranchSettings($branch);

        if (!$config['late_deduction_enabled']) {
            return 0.0;
        }

        if ($lateMinutes <= $config['late_grace_minutes']) {
            return 0.0;
        }

        if ($lateMinutes <= $config['late_quarter_day_minutes']) {
            return 0.25;
        }

        if ($lateMinutes <= $config['late_half_day_minutes']) {
            return 0.50;
        }

        return 1.00;
    }

    /**
     * Calculate total late deduction days for an employee across a given month.
     */
    public static function calculateMonthlyLateDeductionDays(Employee $employee, Carbon $month): float
    {
        $branch = $employee->branch;
        $config = static::getBranchSettings($branch);

        if (!$config['late_deduction_enabled']) {
            return 0.0;
        }

        $attendanceRecords = Attendance::where('employee_id', $employee->id)
            ->whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->where('late_hours', '>', 0)
            ->get();

        $totalDays = 0.0;
        foreach ($attendanceRecords as $record) {
            $totalDays += static::calculateLateDeductionDays((int) $record->late_hours, $branch);
        }

        return round($totalDays, 2);
    }

    /**
     * Calculate total absent days to deduct for an employee based on branch absence multiplier.
     */
    public static function calculateAbsenceDeductionDays(int $absentDaysCount, ?Branch $branch = null): float
    {
        if ($absentDaysCount <= 0) {
            return 0.0;
        }

        $config = static::getBranchSettings($branch);

        $multiplier = $config['absence_penalty_enabled']
            ? max(1.0, $config['absence_deduction_multiplier'])
            : 1.0;

        return round($absentDaysCount * $multiplier, 2);
    }
}
