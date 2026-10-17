<?php

namespace App\Services\AI\Tools;

use App\Enums\InstallmentStatus;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Installment;
use App\Models\InstallmentType;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateInstallmentTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_installment_record';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إضافة قسط مالي أو سلفة موظف إلى النظام مع حساب الأقساط والمتبقي تلقائياً.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'employee_identifier' => [
                        'type' => 'STRING',
                        'description' => 'اسم الموظف أو رقم الهوية/الإقامة أو الرقم الوظيفي'
                    ],
                    'total_amount' => [
                        'type' => 'NUMBER',
                        'description' => 'إجمالي مبلغ السلفة أو القسط بالريال السعودي'
                    ],
                    'months_count' => [
                        'type' => 'INTEGER',
                        'description' => 'مدة التقسيط بالأشهر (الافتراضي 1)'
                    ],
                    'down_payment' => [
                        'type' => 'NUMBER',
                        'description' => 'الدفعة المقدمة إن وجدت (الافتراضي 0)'
                    ],
                    'installment_type' => [
                        'type' => 'STRING',
                        'description' => 'نوع السلفة أو القسط (مثال: سلفة شخصية، قسط مشتريات، قرض)'
                    ],
                    'installment_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ بدء القسط بصيغة YYYY-MM-DD'
                    ],
                    'description' => [
                        'type' => 'STRING',
                        'description' => 'البيان أو الملاحظات'
                    ]
                ],
                'required' => ['employee_identifier', 'total_amount']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $identifier = trim($arguments['employee_identifier'] ?? '');
            $totalAmount = (float)($arguments['total_amount'] ?? 0);
            $monthsCount = max(1, (int)($arguments['months_count'] ?? 1));
            $downPayment = (float)($arguments['down_payment'] ?? 0);
            $date = trim($arguments['installment_date'] ?? date('Y-m-d'));
            $typeName = trim($arguments['installment_type'] ?? 'سلفة شخصية');
            $desc = trim($arguments['description'] ?? '');

            if (empty($identifier)) {
                return ['success' => false, 'message' => 'اسم أو معرف الموظف مطلوب للقسط.'];
            }

            if ($totalAmount <= 0) {
                return ['success' => false, 'message' => 'يجب أن يكون مبلغ القسط أكبر من الصفر.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            // البحث عن الموظف
            $employee = $this->resolveEmployee($identifier, $companyId);
            if (!$employee) {
                return [
                    'success' => false,
                    'message' => "تعذر العثور على الموظف ({$identifier}) في النظام.",
                ];
            }

            // العثور على نوع القسط أو إنشاؤه
            $type = InstallmentType::firstOrCreate(
                ['company_id' => $companyId, 'name' => $typeName ?: 'سلفة شخصية'],
                ['category' => 'advances', 'status' => 'active']
            );

            // منع التكرار (نفس الموظف + نفس المبلغ + نفس التاريخ)
            $duplicate = Installment::where('company_id', $companyId)
                ->where('employee_id', $employee->id)
                ->where('total_amount', $totalAmount)
                ->whereDate('installment_date', $date)
                ->first();

            if ($duplicate) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): قسط/سلفة للموظف ({$employee->user?->name}) بمبلغ " . number_format($totalAmount, 2) . " ر.س مسجلة مسبقاً برقم #{$duplicate->installment_number}، تم تخطيها.",
                ];
            }

            $installment = Installment::create([
                'company_id' => $companyId,
                'employee_id' => $employee->id,
                'installment_type_id' => $type->id,
                'installment_date' => $date,
                'total_amount' => $totalAmount,
                'down_payment' => $downPayment,
                'months_count' => $monthsCount,
                'status' => InstallmentStatus::OPEN,
                'description' => $desc ?: "استيراد ذكي — قسط للموظف {$employee->user?->name}",
            ]);

            return [
                'success' => true,
                'message' => "تم تسجيل قسط/سلفة جديدة بنجاح للموظف ({$employee->user?->name}) بمبلغ " . number_format($totalAmount, 2) . " ر.س برقم #{$installment->installment_number}",
                'record_name' => $employee->user?->name ?? "قسط #{$installment->id}",
                'details' => [
                    'amount' => number_format($totalAmount, 2) . ' ر.س',
                    'monthly_amount' => number_format($installment->monthly_amount, 2) . ' ر.س',
                    'months_count' => $monthsCount,
                ]
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ القسط: ' . $e->getMessage(),
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
