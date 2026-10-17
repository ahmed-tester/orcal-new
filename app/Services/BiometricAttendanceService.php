<?php

namespace App\Services;

use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\AttendanceDevice;
use App\Models\BiometricLog;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BiometricAttendanceService
{
    /**
     * Handle device handshake (GET /iclock/cdata)
     */
    public function handleHandshake(string $sn, array $params = []): string
    {
        $device = AttendanceDevice::where('serial_number', $sn)
            ->orWhere('code', $sn)
            ->first();

        if ($device) {
            $device->update([
                'last_seen_at' => now(),
                'push_version' => $params['pushver'] ?? $device->push_version,
                'status' => 'active',
            ]);
        }

        $nowStamp = time();

        // Standard ZKTeco ADMS plain-text handshake configuration
        return "GET OPTION FROM: {$sn}\n" .
            "STAMP=9999\n" .
            "ATTLOGStamp={$nowStamp}\n" .
            "OPERLOGStamp={$nowStamp}\n" .
            "ErrorDelay=30\n" .
            "Delay=10\n" .
            "TransTimes=00:00;23:59\n" .
            "TransInterval=1\n" .
            "TransFlag=TransData AttLog OpLog EnrollUser ChgUser\n" .
            "Realtime=1\n" .
            "TimeZone=3\n" .
            "Encrypt=None\n";
    }

    /**
     * Process raw attendance logs from device (POST /iclock/cdata?table=ATTLOG)
     */
    public function processAttLog(string $sn, string $rawBody): int
    {
        $device = AttendanceDevice::where('serial_number', $sn)
            ->orWhere('code', $sn)
            ->first();

        if ($device) {
            $device->update(['last_seen_at' => now()]);
        }

        $lines = preg_split("/\r\n|\n|\r/", trim($rawBody));
        $processedCount = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Split TAB-separated (or space-separated fallback) fields
            $cols = preg_split("/\t+|\s{2,}/", $line);
            if (count($cols) < 2) {
                // Try single tab or space split
                $cols = explode("\t", $line);
            }

            $pin = trim($cols[0] ?? '');
            $timeStr = trim($cols[1] ?? '');
            $rawStatus = trim($cols[2] ?? '0');
            $verifyMode = trim($cols[3] ?? '1');
            $workCode = trim($cols[4] ?? '');

            if (empty($pin) || empty($timeStr)) {
                continue;
            }

            try {
                $punchedAt = Carbon::parse($timeStr);
            } catch (\Exception $e) {
                Log::warning("BiometricAttendance: Invalid timestamp format [{$timeStr}] from SN [{$sn}]");
                continue;
            }

            $uniqueKey = md5("{$sn}:{$pin}:{$punchedAt->format('Y-m-d H:i:s')}:{$rawStatus}");

            // Deduplicate at raw log level
            $existingLog = BiometricLog::where('unique_key', $uniqueKey)->first();
            if ($existingLog) {
                $processedCount++;
                continue;
            }

            // Match employee
            $employee = $this->resolveEmployee($pin, $device?->company_id);

            // Determine punch type
            $punchType = $this->determinePunchType($rawStatus);

            DB::beginTransaction();
            try {
                $log = BiometricLog::create([
                    'device_id' => $device?->id,
                    'employee_id' => $employee?->id,
                    'device_sn' => $sn,
                    'device_user_id' => $pin,
                    'punched_at' => $punchedAt,
                    'punch_type' => $punchType,
                    'raw_status' => $rawStatus,
                    'verify_mode' => $verifyMode,
                    'work_code' => $workCode,
                    'raw_payload' => $line,
                    'is_processed' => false,
                    'unique_key' => $uniqueKey,
                ]);

                if ($employee) {
                    $this->syncWithAttendance($employee, $device, $punchedAt, $punchType);
                    $log->update([
                        'is_processed' => true,
                        'processed_at' => now(),
                    ]);
                }

                DB::commit();
                $processedCount++;
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("BiometricAttendance: Failed to process line [{$line}] from SN [{$sn}]: " . $e->getMessage());
            }
        }

        return $processedCount;
    }

    /**
     * Resolve employee from PIN / Device User ID
     */
    /**
     * Resolve employee from PIN / Device User ID
     */
    protected function resolveEmployee(string $pin, ?int $companyId): ?Employee
    {
        $query = Employee::with(['user', 'detail', 'branch', 'company']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        // Try exact match on employee_number
        $emp = (clone $query)->where('employee_number', $pin)->first();
        if ($emp) {
            return $emp;
        }

        // Try match by ID if numeric
        if (is_numeric($pin)) {
            $emp = (clone $query)->where('id', (int)$pin)->first();
            if ($emp) {
                return $emp;
            }
        }

        // Try without company constraint if not found
        if ($companyId) {
            return Employee::with(['user', 'detail', 'branch', 'company'])
                ->where('employee_number', $pin)
                ->orWhere('id', is_numeric($pin) ? (int)$pin : 0)
                ->first();
        }

        return null;
    }

    /**
     * Map raw status code to punch type (0: Check-In, 1: Check-Out)
     */
    protected function determinePunchType(string $rawStatus): string
    {
        return match ($rawStatus) {
            '1' => 'check_out',
            default => 'check_in',
        };
    }

    /**
     * Synchronize biometric punch with Attendance record
     */
    protected function syncWithAttendance(Employee $employee, ?AttendanceDevice $device, Carbon $punchedAt, string $punchType): void
    {
        $punchDate = $punchedAt->toDateString();
        $punchTime = $punchedAt->toTimeString();

        $attendance = Attendance::where('employee_id', $employee->id)
            ->where('date', $punchDate)
            ->first();

        // 1. Branch Attendance & Shift Configuration
        $branch = $employee->branch;
        $branchSettings = AttendancePenaltyService::getBranchSettings($branch);
        $checkInStr = $branchSettings['check_in_time'];
        $toleranceMinutes = $branchSettings['late_grace_minutes'];

        $standardStartTime = Carbon::parse("{$punchDate} {$checkInStr}");
        $graceEndTime = $standardStartTime->copy()->addMinutes($toleranceMinutes);

        $employeeName = $employee->user?->name ?? "موظف #{$employee->id}";
        $branchName = $branch?->name ?? 'بدون فرع مخصص (افتراضي)';
        $companyName = $employee->company?->trade_name ?? '—';
        $deviceName = $device?->name ?? 'جهاز بصمة غير مسجل';
        $deviceSn = $device?->code ?? $device?->serial_number ?? 'UNKNOWN-SN';

        if (!$attendance) {
            // First punch of the day: create Check-In
            $lateMinutes = 0;
            if ($punchedAt->gt($graceEndTime)) {
                $lateMinutes = (int) round(abs((float) $punchedAt->diffInMinutes($standardStartTime)));
            }

            $status = $lateMinutes > 0 ? AttendanceStatus::LATE : AttendanceStatus::PRESENT;

            $attendance = Attendance::create([
                'employee_id' => $employee->id,
                'device_id' => $device?->id,
                'date' => $punchDate,
                'check_in' => $punchTime,
                'check_in_source' => AttendanceSource::FINGERPRINT,
                'late_hours' => $lateMinutes,
                'overtime_hours' => 0.00,
                'total_hours' => 0.00,
                'status' => $status,
                'notes' => "بصمة إلكترونية من جهاز [{$deviceName}]",
            ]);

            // Detailed Log
            Log::info("══════════════════════════════════════════════════════════════════════");
            Log::info("🕒 [تسجيل بصمة دخول جديدة - Biometric Check-In]");
            Log::info("👤 الموظف: {$employeeName} | الرقم الوظيفي: " . ($employee->employee_number ?? $employee->id) . " | ID: {$employee->id}");
            Log::info("🏢 الشركة: {$companyName} | الفرع: {$branchName}");
            Log::info("⚙️ مواعيد الدوام للفرع: بداية الدوام [{$checkInStr}] | فترة السماح [{$toleranceMinutes} دقيقة] | نهاية السماح [{$graceEndTime->format('H:i')}]");
            Log::info("📠 جهاز البصمة: {$deviceName} | الرقم التسلسلي: {$deviceSn}");
            Log::info("⏰ وقت البصمة الفعلي: {$punchedAt->format('Y-m-d H:i:s')}");
            Log::info("📊 نتيجة الاحتساب: دقائق التأخير [{$lateMinutes} دقيقة] | حالة الحضور [" . $status->getLabel() . "]");
            Log::info("💾 تم إنشاء سجل الحضور رقم: #{$attendance->id}");
            Log::info("══════════════════════════════════════════════════════════════════════");
        } else {
            // Existing attendance record today
            if ($punchType === 'check_in' || empty($attendance->check_in)) {
                $lateMinutes = 0;
                if ($punchedAt->gt($graceEndTime)) {
                    $lateMinutes = (int) round(abs((float) $punchedAt->diffInMinutes($standardStartTime)));
                }
                $attendance->check_in = $punchTime;
                $attendance->check_in_source = AttendanceSource::FINGERPRINT;
                $attendance->late_hours = $lateMinutes;
                $attendance->status = $lateMinutes > 0 ? AttendanceStatus::LATE : AttendanceStatus::PRESENT;

                Log::info("══════════════════════════════════════════════════════════════════════");
                Log::info("🔄 [تحديث بصمة دخول لسجل قائم - Biometric Check-In Update]");
                Log::info("👤 الموظف: {$employeeName} | الفرع: {$branchName} | مواعيد الدوام: {$checkInStr} (سماح: {$toleranceMinutes} دقيقة)");
                Log::info("⏰ وقت البصمة: {$punchedAt->format('Y-m-d H:i:s')} | دقائق التأخير: {$lateMinutes} دقيقة | حالة الحضور [" . $attendance->status->getLabel() . "]");
                Log::info("💾 تم تحديث سجل الحضور رقم: #{$attendance->id}");
                Log::info("══════════════════════════════════════════════════════════════════════");
            } else {
                // Update Check-Out
                $attendance->check_out = $punchTime;
                $attendance->check_out_source = AttendanceSource::FINGERPRINT;

                $totalMinutes = 0;
                $totalHours = 0.00;
                $overtimeMinutes = 0;

                if ($attendance->check_in) {
                    $checkInDateTime = Carbon::parse("{$punchDate} {$attendance->check_in}");
                    $checkOutDateTime = $punchedAt;

                    if ($checkOutDateTime->gt($checkInDateTime)) {
                        $totalMinutes = (int) round(abs((float) $checkOutDateTime->diffInMinutes($checkInDateTime)));
                        $totalHours = round($totalMinutes / 60, 2);
                        $attendance->total_hours = $totalHours;

                        // الدوام القياسي 8 ساعات (480 دقيقة) وما زاد يعتبر وقتاً إضافياً بالدقائق
                        $overtimeMinutes = max(0, $totalMinutes - 480);
                        $attendance->overtime_hours = $overtimeMinutes;
                    }
                }

                Log::info("══════════════════════════════════════════════════════════════════════");
                Log::info("🚪 [تسجيل بصمة انصراف - Biometric Check-Out]");
                Log::info("👤 الموظف: {$employeeName} | الفرع: {$branchName}");
                Log::info("⏰ وقت الدخول السابق: {$attendance->check_in} | وقت الانصراف الآن: {$punchTime}");
                Log::info("⏱️ إجمالي مدة العمل: {$totalMinutes} دقيقة ({$totalHours} ساعة) | دقائق إضافية: {$overtimeMinutes} دقيقة");
                Log::info("💾 تم تحديث سجل الحضور رقم: #{$attendance->id}");
                Log::info("══════════════════════════════════════════════════════════════════════");
            }

            if ($device) {
                $attendance->device_id = $device->id;
            }

            $attendance->save();
        }
    }
}
