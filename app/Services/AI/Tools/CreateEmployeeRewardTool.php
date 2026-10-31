<?php

namespace App\Services\AI\Tools;

use App\Enums\RewardStatus;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeReward;
use App\Services\AI\Contracts\GeminiToolInterface;
use App\Services\AI\EmployeeNameMatcher;

class CreateEmployeeRewardTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_employee_reward';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج مكافأة مالية أو إكرامية أو بدل إنجاز لموظف مع تحديد المبلغ والسبب والتاريخ.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'employee_identifier' => [
                        'type' => 'STRING',
                        'description' => 'اسم الموظف أو رقم الهوية أو الرقم الوظيفي',
                    ],
                    'amount' => [
                        'type' => 'NUMBER',
                        'description' => 'مبلغ المكافأة بالريال السعودي',
                    ],
                    'reward_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ استحقاق المكافأة بصيغة YYYY-MM-DD',
                    ],
                    'reason' => [
                        'type' => 'STRING',
                        'description' => 'سبب أو مناسبة المكافأة (مثال: تميز في العمل، إنجاز مشروع، إكرامية عيد)',
                    ],
                    'hours_count' => [
                        'type' => 'NUMBER',
                        'description' => 'عدد الساعات الإضافية المرتبطة بالمكافأة إن وجدت',
                    ],
                ],
                'required' => ['employee_identifier', 'amount'],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $identifier = trim($arguments['employee_identifier'] ?? '');
            $amount = (float) ($arguments['amount'] ?? 0);
            $date = trim($arguments['reward_date'] ?? date('Y-m-d'));
            $reason = trim($arguments['reason'] ?? '');
            $hours = (float) ($arguments['hours_count'] ?? 0);

            if (empty($identifier)) {
                return ['success' => false, 'message' => 'اسم أو معرف الموظف مطلوب للمكافأة.'];
            }

            if ($amount <= 0) {
                return ['success' => false, 'message' => 'مبلغ المكافأة يجب أن يكون أكبر من الصفر.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $employee = $this->resolveEmployee($identifier, $companyId);
            if (! $employee) {
                return [
                    'success' => false,
                    'message' => "تعذر العثور على الموظف ({$identifier}) في النظام لإدراج المكافأة.",
                ];
            }

            // فحص التكرار (نفس الموظف + نفس المبلغ + نفس التاريخ)
            $existing = EmployeeReward::where('employee_id', $employee->id)
                ->where('net_amount', $amount)
                ->whereDate('reward_date', $date)
                ->first();

            if ($existing) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): مكافأة للموظف ({$employee->user?->name}) بمبلغ ".number_format($amount, 2)." ر.س مسجلة مسبقاً برقم #{$existing->reward_number}، تم تخطيها.",
                ];
            }

            $reward = EmployeeReward::create([
                'company_id' => $companyId,
                'employee_id' => $employee->id,
                'reward_date' => $date,
                'net_amount' => $amount,
                'hours_amount' => $amount,
                'hours_count' => $hours,
                'reason' => $reason ?: 'استيراد ذكي — مكافأة موظف',
                'status' => RewardStatus::APPROVED,
                'created_by' => auth()->id(),
            ]);

            return [
                'success' => true,
                'message' => "تم تسجيل مكافأة جديدة للموظف ({$employee->user?->name}) بمبلغ ".number_format($amount, 2)." ر.س برقم #{$reward->reward_number}",
                'record_name' => $employee->user?->name ?? "مكافأة #{$reward->id}",
                'details' => [
                    'amount' => number_format($amount, 2).' ر.س',
                    'date' => $date,
                    'reason' => $reason ?: 'مكافأة تميز',
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ المكافأة: '.$e->getMessage(),
            ];
        }
    }

    protected function resolveEmployee(string $identifier, ?int $companyId = null): ?Employee
    {
        return EmployeeNameMatcher::resolveEmployee($identifier, $companyId);
    }
}
