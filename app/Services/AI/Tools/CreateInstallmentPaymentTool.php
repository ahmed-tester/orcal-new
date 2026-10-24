<?php

namespace App\Services\AI\Tools;

use App\Enums\InstallmentPaymentSource;
use App\Enums\InstallmentStatus;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Installment;
use App\Models\InstallmentPayment;
use App\Services\AI\Contracts\GeminiToolInterface;
use App\Services\AI\EmployeeNameMatcher;

class CreateInstallmentPaymentTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_installment_payment';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'تسجيل سند سداد أو دفعة مسددة لقسط/كفالة/سلفة لموظف مسجل مسبقاً، مع تحديث الرصيد المتبقي تلقائياً. لا تنشئ موظفاً ولا قسطاً جديداً.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'employee_identifier' => [
                        'type' => 'STRING',
                        'description' => 'اسم الموظف أو رقمه الوظيفي أو رقم الهوية/الإقامة',
                    ],
                    'amount' => [
                        'type' => 'NUMBER',
                        'description' => 'المبلغ المسدد بالريال السعودي',
                    ],
                    'payment_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ السداد بصيغة YYYY-MM-DD',
                    ],
                    'installment_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ القسط (أو الشهر) الذي يتم السداد عنه بصيغة YYYY-MM-DD — يُستخدم لربط السداد بالقسط الصحيح',
                    ],
                    'installment_number' => [
                        'type' => 'STRING',
                        'description' => 'رقم القسط في النظام إن كان مذكوراً في الملف (اختياري)',
                    ],
                    'company_name' => [
                        'type' => 'STRING',
                        'description' => 'اسم المؤسسة أو الشركة إن وجد (اختياري، للمطابقة فقط)',
                    ],
                    'operator_name' => [
                        'type' => 'STRING',
                        'description' => 'اسم منفذ أو محصل العملية إن وجد (اختياري)',
                    ],
                    'description' => [
                        'type' => 'STRING',
                        'description' => 'البيان أو تفاصيل السداد (مثال: سداد كفالة شهر 5/2026)',
                    ],
                    'company_id' => [
                        'type' => 'INTEGER',
                        'description' => 'معرف المؤسسة المحددة من شاشة الاستيراد (لا تمرره من الملف)',
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
            $date = trim($arguments['payment_date'] ?? date('Y-m-d'));
            $installmentDate = trim($arguments['installment_date'] ?? '');
            $installmentNumber = trim((string) ($arguments['installment_number'] ?? ''));
            $companyName = trim($arguments['company_name'] ?? '');
            $operatorName = trim($arguments['operator_name'] ?? $arguments['executed_by'] ?? '');
            $desc = trim($arguments['description'] ?? '');

            if (empty($identifier)) {
                return ['success' => false, 'message' => 'اسم أو معرف الموظف مطلوب لتسجيل السداد.'];
            }

            if ($amount <= 0) {
                return ['success' => false, 'message' => 'يجب أن يكون المبلغ المسدد أكبر من الصفر.'];
            }

            // 1. الموظف يجب أن يكون مسجلاً مسبقاً — لا يتم إنشاء موظفين من ملف مستورد.
            $employee = $this->findEmployee($identifier);

            if (! $employee) {
                return [
                    'success' => false,
                    'message' => __('smart_import.service.employee_not_found', ['name' => $identifier]),
                ];
            }

            // 2. القسط المستهدف: بالرقم، أو بتاريخ القسط، أو آخر قسط مفتوح للموظف.
            $installment = $this->findInstallment($employee, $installmentNumber, $installmentDate);

            if (! $installment) {
                return [
                    'success' => false,
                    'message' => __('smart_import.service.installment_not_found', [
                        'name' => $employee->user?->name ?? $identifier,
                        'date' => $installmentDate !== '' ? $installmentDate : $date,
                    ]),
                ];
            }

            // 3. تسوية منفذ العملية إن وجد
            $executedById = $this->resolveOperator($operatorName);

            // 4. منع التكرار: نفس القسط + المبلغ + التاريخ + البيان
            $duplicateQuery = InstallmentPayment::where('installment_id', $installment->id)
                ->where('amount', $amount)
                ->whereDate('payment_date', $date);

            if ($desc !== '') {
                $duplicateQuery->where('description', $desc);
            }

            $duplicate = $duplicateQuery->first();

            if ($duplicate) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): سند سداد للموظف ({$employee->user?->name}) بمبلغ ".number_format($amount, 2)." ر.س مسجل مسبقاً برقم #{$duplicate->voucher_number}، تم تخطيه.",
                ];
            }

            // 5. تسجيل سند السداد
            $companyId = $installment->company_id
                ?: $this->resolveCompanyId($arguments['company_id'] ?? null, $companyName, $employee);

            $payment = InstallmentPayment::create([
                'company_id' => $companyId,
                'employee_id' => $employee->id,
                'installment_id' => $installment->id,
                'payment_type' => $installment->transaction_type ?? 'installment',
                'payment_date' => $date,
                'amount' => $amount,
                'payment_source' => InstallmentPaymentSource::CASH,
                'executed_by' => $executedById ?: auth()->user()?->employee?->id,
                'description' => $desc !== '' ? $desc : "سداد قسط للموظف {$employee->user?->name}",
            ]);

            return [
                'success' => true,
                'message' => "تم تسجيل سند سداد بنجاح للموظف ({$employee->user?->name}) بمبلغ ".number_format($amount, 2)." ر.س برقم #{$payment->voucher_number}",
                'record_name' => $employee->user?->name ?? "سند #{$payment->id}",
                'details' => [
                    'amount' => number_format($amount, 2).' ر.س',
                    'voucher_number' => $payment->voucher_number,
                    'installment_number' => $installment->installment_number,
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل سند السداد: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Find the installment a payment belongs to, without guessing when a date is given.
     */
    protected function findInstallment(Employee $employee, string $installmentNumber, string $installmentDate): ?Installment
    {
        if ($installmentNumber !== '') {
            $byNumber = Installment::where('employee_id', $employee->id)
                ->where('installment_number', $installmentNumber)
                ->first();

            if ($byNumber) {
                return $byNumber;
            }
        }

        if ($installmentDate !== '') {
            $byDate = Installment::where('employee_id', $employee->id)
                ->whereDate('installment_date', $installmentDate)
                ->orderByDesc('id')
                ->first();

            if ($byDate) {
                return $byDate;
            }
        }

        return Installment::where('employee_id', $employee->id)
            ->where('status', '!=', InstallmentStatus::COMPLETED)
            ->orderByDesc('id')
            ->first()
            ?? Installment::where('employee_id', $employee->id)->orderByDesc('id')->first();
    }

    /**
     * Resolve the target company: explicit selection > exact/partial name match >
     * the employee's own company. Companies are never created from a file here.
     */
    protected function resolveCompanyId(mixed $explicitCompanyId, string $companyName, Employee $employee): ?int
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

        return $employee->company_id;
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
