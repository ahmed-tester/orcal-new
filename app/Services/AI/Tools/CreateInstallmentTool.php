<?php

namespace App\Services\AI\Tools;

use App\Enums\InstallmentStatus;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Installment;
use App\Models\InstallmentType;
use App\Services\AI\Contracts\GeminiToolInterface;
use App\Services\AI\EmployeeNameMatcher;

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
            'description' => 'إضافة قسط مالي أو كفالة أو سلفة لموظف مسجل مسبقاً في النظام مع حساب الأقساط والمتبقي تلقائياً. لا تنشئ موظفاً جديداً: استخدم اسم الموظف كما هو مكتوب في الملف.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'employee_identifier' => [
                        'type' => 'STRING',
                        'description' => 'اسم الموظف أو رقم الهوية/الإقامة أو الرقم الوظيفي',
                    ],
                    'total_amount' => [
                        'type' => 'NUMBER',
                        'description' => 'إجمالي مبلغ السلفة أو القسط بالريال السعودي',
                    ],
                    'company_name' => [
                        'type' => 'STRING',
                        'description' => 'اسم المؤسسة أو الشركة التابع لها العامل إن وجدت (اختياري)',
                    ],
                    'operator_name' => [
                        'type' => 'STRING',
                        'description' => 'اسم منفذ أو مسجل العملية إن وجد (اختياري)',
                    ],
                    'months_count' => [
                        'type' => 'INTEGER',
                        'description' => 'مدة التقسيط بالأشهر (الافتراضي 1)',
                    ],
                    'down_payment' => [
                        'type' => 'NUMBER',
                        'description' => 'الدفعة المقدمة إن وجدت (الافتراضي 0)',
                    ],
                    'installment_type' => [
                        'type' => 'STRING',
                        'description' => 'نوع السلفة أو القسط (مثال: كفالات شهرية، تجديد إقامة، مخالفات، سلفة شخصية)',
                    ],
                    'installment_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ القسط بصيغة YYYY-MM-DD',
                    ],
                    'description' => [
                        'type' => 'STRING',
                        'description' => 'البيان أو الملاحظات الواردة في الكشف (مثال: كفالة شهر 5/2026)',
                    ],
                    'company_id' => [
                        'type' => 'INTEGER',
                        'description' => 'معرف المؤسسة المحددة من شاشة الاستيراد (لا تمرره من الملف)',
                    ],
                ],
                'required' => ['employee_identifier', 'total_amount'],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $identifier = trim($arguments['employee_identifier'] ?? '');
            $totalAmount = (float) ($arguments['total_amount'] ?? 0);
            $monthsCount = max(1, (int) ($arguments['months_count'] ?? 1));
            $downPayment = (float) ($arguments['down_payment'] ?? 0);
            $date = trim($arguments['installment_date'] ?? date('Y-m-d'));
            $companyName = trim($arguments['company_name'] ?? '');
            $operatorName = trim($arguments['operator_name'] ?? $arguments['executed_by'] ?? '');
            $typeName = trim($arguments['installment_type'] ?? 'كفالات');
            $desc = trim($arguments['description'] ?? '');

            if (empty($identifier)) {
                return ['success' => false, 'message' => 'اسم أو معرف الموظف مطلوب للقسط.'];
            }

            if ($totalAmount <= 0) {
                return ['success' => false, 'message' => 'يجب أن يكون مبلغ القسط أكبر من الصفر.'];
            }

            // 1. العثور على الموظف أو إنشاؤه تلقائياً إن لم يكن مسجلاً
            $employee = EmployeeNameMatcher::findOrCreate($identifier, $arguments['company_id'] ?? null, $companyName);
            $isNewEmployee = $employee->wasRecentlyCreated;

            // 2. المؤسسة: المحددة من الشاشة، أو المطابقة بالاسم، أو مؤسسة الموظف.
            $companyId = $this->resolveCompanyId($arguments['company_id'] ?? null, $companyName, $employee);
            if ($companyId && $employee->company_id !== $companyId) {
                $employee->update(['company_id' => $companyId]);
            }

            // 3. تسوية منفذ العملية إن وجد
            $executedById = $this->resolveOperator($operatorName);

            // 4. العثور على نوع القسط أو إنشاؤه
            $type = InstallmentType::firstOrCreate(
                ['company_id' => $companyId, 'name' => $typeName ?: 'كفالات'],
                ['category' => 'advances', 'status' => 'active']
            );

            // 5. منع التكرار: نفس الموظف + المبلغ + التاريخ + النوع + البيان
            //    (البيان ضروري لأن الكشف قد يحتوي شهرين بنفس المبلغ والتاريخ).
            $duplicateQuery = Installment::where('employee_id', $employee->id)
                ->where('total_amount', $totalAmount)
                ->whereDate('installment_date', $date)
                ->where('installment_type_id', $type->id);

            if ($desc !== '') {
                $duplicateQuery->where('description', $desc);
            }

            $duplicate = $duplicateQuery->first();

            if ($duplicate) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): قسط/سلفة للموظف ({$employee->user?->name}) بمبلغ ".number_format($totalAmount, 2)." ر.س مسجلة مسبقاً برقم #{$duplicate->installment_number}، تم تخطيها.",
                ];
            }

            // 6. إنشاء القسط
            $installment = Installment::create([
                'company_id' => $companyId,
                'employee_id' => $employee->id,
                'installment_type_id' => $type->id,
                'installment_date' => $date,
                'total_amount' => $totalAmount,
                'down_payment' => $downPayment,
                'months_count' => $monthsCount,
                'status' => InstallmentStatus::OPEN,
                'executed_by' => $executedById ?: auth()->user()?->employee?->id,
                'description' => $desc !== '' ? $desc : "قسط للموظف {$employee->user?->name}",
            ]);

            $newNotice = $isNewEmployee ? ' (تم إنشاء حساب الموظف وتعيينه تلقائياً)' : '';

            return [
                'success' => true,
                'message' => "تم تسجيل قسط/سلفة جديدة بنجاح للموظف ({$employee->user?->name}) بمبلغ ".number_format($totalAmount, 2)." ر.س برقم #{$installment->installment_number}".$newNotice,
                'record_name' => $employee->user?->name ?? "قسط #{$installment->id}",
                'details' => [
                    'amount' => number_format($totalAmount, 2).' ر.س',
                    'monthly_amount' => number_format($installment->monthly_amount, 2).' ر.س',
                    'months_count' => $monthsCount,
                    'installment_number' => $installment->installment_number,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ القسط: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Resolve the target company: explicit selection > exact/partial name match >
     * the employee's own company.
     */
    protected function resolveCompanyId(mixed $explicitCompanyId, string $companyName, ?Employee $employee = null): ?int
    {
        if (! empty($explicitCompanyId) && Company::whereKey($explicitCompanyId)->exists()) {
            return (int) $explicitCompanyId;
        }

        if ($companyName !== '') {
            $company = Company::where('trade_name', 'like', "%{$companyName}%")->first();

            if ($company) {
                return $company->id;
            }
        }

        return $employee?->company_id ?? Company::first()?->id;
    }

    /**
     * Find an existing employee by id, employee number, or name. Never creates one.
     */
    protected function findEmployee(string $identifier): ?Employee
    {
        if ($identifier === '') {
            return null;
        }

        if (is_numeric($identifier)) {
            $employee = Employee::where(fn ($q) => $q->where('id', $identifier)->orWhere('employee_number', $identifier))
                ->orWhereHas('saudiDetail', fn ($q) => $q->where('national_id', $identifier))
                ->orWhereHas('foreignDetail', fn ($q) => $q->where('iqama_number', $identifier))
                ->first();

            if ($employee) {
                return $employee;
            }
        }

        // Direct exact match on User name
        $direct = Employee::whereHas('user', fn ($q) => $q->where('name', $identifier))->first();
        if ($direct) {
            return $direct;
        }

        return EmployeeNameMatcher::find($identifier);
    }

    protected function resolveOperator(string $operatorName): ?int
    {
        if (empty($operatorName)) {
            return null;
        }

        return EmployeeNameMatcher::find($operatorName)?->id;
    }
}
