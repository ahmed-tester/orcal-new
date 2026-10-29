<?php

namespace App\Services\AI\Tools;

use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Services\AI\Contracts\GeminiToolInterface;
use App\Services\AI\EmployeeNameMatcher;

class CreateAttendanceTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_attendance_record';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج سجل حضور وانصراف أو بصمة موظف ليوم محدد مع وقت الدخول والخروج وساعات التأخير أو الإضافي.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'employee_identifier' => [
                        'type' => 'STRING',
                        'description' => 'اسم الموظف أو رقم الهوية أو الرقم الوظيفي',
                    ],
                    'date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ الحضور بصيغة YYYY-MM-DD',
                    ],
                    'check_in' => [
                        'type' => 'STRING',
                        'description' => 'وقت الحضور بصيغة HH:MM (مثال: 08:00)',
                    ],
                    'check_out' => [
                        'type' => 'STRING',
                        'description' => 'وقت الانصراف بصيغة HH:MM (مثال: 16:30)',
                    ],
                    'late_hours' => [
                        'type' => 'NUMBER',
                        'description' => 'ساعات التأخير إن وجدت',
                    ],
                    'overtime_hours' => [
                        'type' => 'NUMBER',
                        'description' => 'ساعات العمل الإضافي إن وجدت',
                    ],
                    'status' => [
                        'type' => 'STRING',
                        'description' => 'حالة الحضور: present (حاضر), absent (غائب), late (متأخر), leave (إجازة)',
                    ],
                    'notes' => [
                        'type' => 'STRING',
                        'description' => 'ملاحظات',
                    ],
                ],
                'required' => ['employee_identifier', 'date'],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $identifier = trim($arguments['employee_identifier'] ?? '');
            $date = trim($arguments['date'] ?? date('Y-m-d'));
            $checkIn = ! empty($arguments['check_in']) ? trim($arguments['check_in']) : null;
            $checkOut = ! empty($arguments['check_out']) ? trim($arguments['check_out']) : null;
            $lateHours = (float) ($arguments['late_hours'] ?? 0);
            $overtimeHours = (float) ($arguments['overtime_hours'] ?? 0);
            $statusStr = strtolower(trim($arguments['status'] ?? 'present'));
            $notes = trim($arguments['notes'] ?? '');

            if (empty($identifier)) {
                return ['success' => false, 'message' => 'اسم أو معرف الموظف مطلوب لسجل الحضور.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $employee = EmployeeNameMatcher::findOrCreate($identifier, $companyId);

            $status = match ($statusStr) {
                'absent', 'غائب', 'غياب' => AttendanceStatus::ABSENT,
                'late', 'متأخر', 'تأخير' => AttendanceStatus::LATE,
                'leave', 'إجازة' => AttendanceStatus::LEAVE,
                default => AttendanceStatus::PRESENT,
            };

            // حساب إجمالي الساعات التقريبية
            $totalHours = 0;
            if ($checkIn && $checkOut) {
                $t1 = strtotime("{$date} {$checkIn}");
                $t2 = strtotime("{$date} {$checkOut}");
                if ($t2 > $t1) {
                    $totalHours = round(($t2 - $t1) / 3600, 2);
                }
            }

            // فحص التكرار (سجل الموظف لنفس اليوم)
            $existing = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $date)
                ->first();

            if ($existing) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): سجل حضور للموظف ({$employee->user?->name}) بتاريخ ({$date}) موجود مسبقاً، تم تخطيه.",
                ];
            }

            Attendance::create([
                'employee_id' => $employee->id,
                'date' => $date,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'late_hours' => $lateHours,
                'overtime_hours' => $overtimeHours,
                'total_hours' => $totalHours,
                'status' => $status,
                'check_in_source' => AttendanceSource::IMPORT,
                'check_out_source' => AttendanceSource::IMPORT,
                'notes' => $notes ?: 'استيراد ذكي — سجل حضور',
                'created_by' => auth()->id(),
            ]);

            return [
                'success' => true,
                'message' => "تم تسجيل حضور الموظف ({$employee->user?->name}) بتاريخ ({$date}) بنجاح (دخول: ".($checkIn ?: '--').' | خروج: '.($checkOut ?: '--').')',
                'record_name' => $employee->user?->name ?? 'سجل حضور',
                'details' => [
                    'date' => $date,
                    'check_in' => $checkIn ?: '--',
                    'check_out' => $checkOut ?: '--',
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ سجل الحضور: '.$e->getMessage(),
            ];
        }
    }

    protected function resolveEmployee(string $identifier, ?int $companyId = null): ?Employee
    {
        return EmployeeNameMatcher::resolveEmployee($identifier, $companyId);
    }
}
