<?php

namespace App\Services\AI\Tools;

use App\Enums\LeaveStatus;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Services\AI\Contracts\GeminiToolInterface;
use Carbon\Carbon;

class CreateLeaveTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_leave_record';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج سجل إجازة موظف (سنوية، مرضية، اعتيادية، إلخ) مع تحديد تواريخ البداية والنهاية وعدد الأيام.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'employee_identifier' => [
                        'type' => 'STRING',
                        'description' => 'اسم الموظف أو رقم الهوية أو الرقم الوظيفي'
                    ],
                    'leave_type' => [
                        'type' => 'STRING',
                        'description' => 'نوع الإجازة (مثال: سنوية، مرضية، طارئة، بدون راتب)'
                    ],
                    'start_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ بداية الإجازة بصيغة YYYY-MM-DD'
                    ],
                    'end_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ نهاية الإجازة بصيغة YYYY-MM-DD'
                    ],
                    'days' => [
                        'type' => 'INTEGER',
                        'description' => 'عدد أيام الإجازة (اختياري، يتم حسابه تلقائياً إذا لم يحدد)'
                    ],
                    'reason' => [
                        'type' => 'STRING',
                        'description' => 'سبب أو مبرر الإجازة'
                    ],
                    'status' => [
                        'type' => 'STRING',
                        'description' => 'حالة الإجازة: approved (معتمدة), pending (معلقة), rejected (مرفوضة)'
                    ]
                ],
                'required' => ['employee_identifier', 'start_date', 'end_date']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $identifier = trim($arguments['employee_identifier'] ?? '');
            $startDate = trim($arguments['start_date'] ?? '');
            $endDate = trim($arguments['end_date'] ?? '');
            $typeName = trim($arguments['leave_type'] ?? 'سنوية');
            $reason = trim($arguments['reason'] ?? '');
            $statusStr = strtolower(trim($arguments['status'] ?? 'approved'));

            if (empty($identifier)) {
                return ['success' => false, 'message' => 'اسم أو معرف الموظف مطلوب للإجازة.'];
            }

            if (empty($startDate) || empty($endDate)) {
                return ['success' => false, 'message' => 'تاريخ بداية ونهاية الإجازة مطلوبان.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $employee = $this->resolveEmployee($identifier, $companyId);
            if (!$employee) {
                return [
                    'success' => false,
                    'message' => "تعذر العثور على الموظف ({$identifier}) في النظام لإدراج الإجازة.",
                ];
            }

            // حساب عدد الأيام
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            $days = max(1, (int)($arguments['days'] ?? ($end->diffInDays($start) + 1)));

            // العثور على نوع الإجازة أو إنشاؤه
            $type = LeaveType::firstOrCreate(
                ['company_id' => $companyId, 'name' => $typeName ?: 'سنوية'],
                ['days_per_year' => 30, 'is_paid' => true, 'status' => 'active']
            );

            // فحص منع التكرار (تطابق الموظف وتاريخ البداية)
            $existing = Leave::where('employee_id', $employee->id)
                ->whereDate('start_date', $startDate)
                ->first();

            if ($existing) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): إجازة للموظف ({$employee->user?->name}) تبدأ بتاريخ ({$startDate}) موجودة مسبقاً، تم تخطيها.",
                ];
            }

            $status = match ($statusStr) {
                'pending', 'معلق', 'قيد الانتظار' => LeaveStatus::PENDING,
                'rejected', 'مرفوض' => LeaveStatus::REJECTED,
                default => LeaveStatus::APPROVED,
            };

            $leave = Leave::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $type->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days' => $days,
                'reason' => $reason ?: 'استيراد ذكي — إجازة موظف',
                'status' => $status,
                'approved_by' => $status === LeaveStatus::APPROVED ? auth()->id() : null,
                'approved_at' => $status === LeaveStatus::APPROVED ? now() : null,
            ]);

            return [
                'success' => true,
                'message' => "تم تسجيل إجازة ({$type->name}) للموظف ({$employee->user?->name}) لمدة ({$days} أيام) من {$startDate} إلى {$endDate}",
                'record_name' => $employee->user?->name ?? "إجازة #{$leave->id}",
                'details' => [
                    'days' => "{$days} أيام",
                    'type' => $type->name,
                    'start_date' => $startDate,
                ]
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ الإجازة: ' . $e->getMessage(),
            ];
        }
    }

    protected function resolveEmployee(string $identifier, ?int $companyId = null): ?Employee
    {
        $query = Employee::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if (is_numeric($identifier)) {
            $emp = (clone $query)->where('id', $identifier)->first();
            if ($emp) return $emp;
        }

        $emp = (clone $query)->where('employee_number', $identifier)->first();
        if ($emp) return $emp;

        $emp = (clone $query)->whereHas('saudiDetail', fn($q) => $q->where('national_id', $identifier))
            ->orWhereHas('foreignDetail', fn($q) => $q->where('iqama_number', $identifier))
            ->first();
        if ($emp) return $emp;

        return (clone $query)->whereHas('user', fn($q) => $q->where('name', 'like', "%{$identifier}%")->orWhere('phone', 'like', "%{$identifier}%"))->first();
    }
}
