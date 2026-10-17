<?php

namespace App\Console\Commands;

use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use App\Enums\LeaveStatus;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkAbsentEmployees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:mark-absent {--branch_id= : Specific branch ID to mark absent}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Marks employees as absent or on approved leave if they missed the check-in time and tolerance.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();
        $now = Carbon::now();

        $branchIdOption = $this->option('branch_id');

        $query = Branch::where('status', 'active');
        if ($branchIdOption) {
            $query->where('id', $branchIdOption);
        }

        $branches = $query->get();

        $absentCount = 0;
        $leaveCount = 0;

        foreach ($branches as $branch) {
            $settings = $branch->attendance_settings ?? [];
            $checkInStr = $settings['check_in_time'] ?? '08:00';
            $toleranceMinutes = (int) ($settings['late_tolerance_minutes'] ?? 15);

            try {
                // Ensure format parsing is robust
                $checkInTime = Carbon::parse($checkInStr)->setDate($today->year, $today->month, $today->day);
            } catch (\Exception $e) {
                $checkInTime = Carbon::create($today->year, $today->month, $today->day, 8, 0, 0);
            }

            // Cutoff time = Check-in time + Tolerance
            $cutoffTime = $checkInTime->copy()->addMinutes($toleranceMinutes);

            // If current time hasn't passed the cutoff time, skip this branch for now
            if ($now->lessThanOrEqualTo($cutoffTime)) {
                continue;
            }

            // Time has passed! Get all employees in this branch
            $employees = Employee::where('branch_id', $branch->id)->where('status', 'active')->get();
            if ($employees->isEmpty()) {
                // Also handle employees without explicit status if not set
                $employees = Employee::where('branch_id', $branch->id)->get();
            }

            foreach ($employees as $employee) {
                // 1. Check if employee already has an attendance record for today
                $hasAttendance = Attendance::where('employee_id', $employee->id)
                    ->whereDate('date', $today)
                    ->exists();

                if ($hasAttendance) {
                    continue;
                }

                // 2. Check if employee is on an approved leave today
                $leave = Leave::with('leaveType')
                    ->where('employee_id', $employee->id)
                    ->where(function ($q) {
                        $q->where('status', LeaveStatus::APPROVED)
                          ->orWhere('status', 'approved');
                    })
                    ->whereDate('start_date', '<=', $today)
                    ->whereDate('end_date', '>=', $today)
                    ->first();

                if ($leave) {
                    $leaveTypeName = $leave->leaveType?->name ?? 'إجازة رسمية';
                    Attendance::create([
                        'employee_id'      => $employee->id,
                        'date'             => $today->format('Y-m-d'),
                        'status'           => AttendanceStatus::LEAVE,
                        'check_in_source'  => AttendanceSource::SYSTEM,
                        'check_out_source' => AttendanceSource::SYSTEM,
                        'notes'            => "إجازة معتمدة: {$leaveTypeName}",
                        'late_hours'       => 0,
                        'overtime_hours'   => 0,
                        'total_hours'      => 0,
                    ]);

                    $leaveCount++;
                    continue;
                }

                // 3. Mark as absent
                Attendance::create([
                    'employee_id'      => $employee->id,
                    'date'             => $today->format('Y-m-d'),
                    'status'           => AttendanceStatus::ABSENT,
                    'check_in_source'  => AttendanceSource::SYSTEM,
                    'check_out_source' => AttendanceSource::SYSTEM,
                    'notes'            => 'تم تسجيل الغياب تلقائياً لعدم الحضور بعد انتهاء فترة السماح',
                    'late_hours'       => 0,
                    'overtime_hours'   => 0,
                    'total_hours'      => 0,
                ]);

                $absentCount++;
            }
        }

        \Illuminate\Support\Facades\Log::info("⏰ [Cron Job] تم تنفيذ أمر فحص الغياب (attendance:mark-absent) بنجاح في: " . $now->toDateTimeString() . " | تم رصد ({$absentCount}) غياب، و ({$leaveCount}) إجازة.");

        if ($absentCount > 0 || $leaveCount > 0) {
            \Illuminate\Support\Facades\Log::channel('attendance')->info("📋 [رصد الحضور الآلي] تم تسجيل ({$absentCount}) موظف كغائب و ({$leaveCount}) في إجازة معتمدة بتاريخ: {$today->toDateString()}");
        }

        $this->info("Processed attendance check: Marked {$absentCount} absent, {$leaveCount} on approved leave.");
    }
}
