<?php

namespace App\Services\AI\Tools;

use App\Enums\InstallmentStatus;
use App\Models\Employee;
use App\Models\Installment;
use App\Models\InstallmentType;
use App\Services\AI\Contracts\GeminiToolInterface;
use App\Services\AI\EmployeeNameMatcher;
use App\Services\CompanyFormService;

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
                        'description' => 'اسم الموظف كما هو في الكشف (إذا كان ثنائي اللغة بالعربي والإنجليزي مثل "محمد حسين MOHAMMAD HOSSAIN" استخرجه كاملاً باللغتين دون حذف أي جزء)، أو رقم الهوية/الإقامة',
                    ],
                    'employee_name_ar' => [
                        'type' => 'STRING',
                        'description' => 'اسم الموظف باللغة العربية إن وجد في الكشف (مثال: محمد حسين)',
                    ],
                    'employee_name_en' => [
                        'type' => 'STRING',
                        'description' => 'اسم الموظف باللغة الإنجليزية إن وجد في الكشف (مثال: MOHAMMAD HOSSAIN)',
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
            $identifier = trim($arguments['employee_identifier'] ?? $arguments['name'] ?? '');
            $explicitNameAr = trim($arguments['employee_name_ar'] ?? $arguments['name_ar'] ?? '');
            $explicitNameEn = trim($arguments['employee_name_en'] ?? $arguments['name_en'] ?? '');

            if ($explicitNameAr !== '' && ! str_contains($identifier, $explicitNameAr)) {
                $identifier = trim($identifier.' '.$explicitNameAr);
            }
            if ($explicitNameEn !== '' && ! str_contains(strtolower($identifier), strtolower($explicitNameEn))) {
                $identifier = trim($identifier.' '.$explicitNameEn);
            }

            $totalAmount = (float) ($arguments['total_amount'] ?? 0);
            $monthsCount = max(1, (int) ($arguments['months_count'] ?? 1));
            $downPayment = (float) ($arguments['down_payment'] ?? 0);
            $date = trim($arguments['installment_date'] ?? date('Y-m-d'));
            $companyName = trim($arguments['company_name'] ?? '');
            $operatorName = trim($arguments['operator_name'] ?? $arguments['executed_by'] ?? '');
            $typeName = trim($arguments['installment_type'] ?? 'كفالات');
            $desc = trim($arguments['description'] ?? '');

            if (empty($identifier) && empty($explicitNameAr) && empty($explicitNameEn)) {
                return ['success' => false, 'message' => 'اسم أو معرف الموظف مطلوب للقسط.'];
            }

            if ($totalAmount <= 0) {
                return ['success' => false, 'message' => 'تم تخطي السطر لأن مبلغ القسط يساوي صفراً أو غير محدد.'];
            }

            // 1. Resolve or CREATE the company first. A statement whose company is
            //    missing must always get a real company record — never a silent
            //    fallback under an unrelated company.
            $explicitCompanyId = ! empty($arguments['company_id']) ? (int) $arguments['company_id'] : null;
            $companyId = null;
            if ($companyName !== '') {
                $companyRes = CompanyFormService::resolveOrCreateCompanyByName($companyName, $explicitCompanyId);
                $companyId = ! empty($companyRes['company_id']) ? (int) $companyRes['company_id'] : null;
            }

            // 2. Employee exists → attach the installment to him; missing → create him.
            $employee = EmployeeNameMatcher::findOrCreate(
                $identifier,
                $companyId ?? $explicitCompanyId,
                null,
                $explicitNameAr !== '' ? $explicitNameAr : null,
                $explicitNameEn !== '' ? $explicitNameEn : null
            );
            $isNewEmployee = $employee->wasRecentlyCreated;

            // 3. Company: statement company, otherwise the employee's own company.
            $companyId ??= $employee->company_id ?? $explicitCompanyId;

            if ($companyId && $employee->company_id !== $companyId) {
                $employee->update(['company_id' => $companyId]);
            }

            // 4. Resolve operator
            $executedById = $this->resolveOperator($operatorName);

            // 5. Resolve or create installment type
            $type = InstallmentType::firstOrCreate(
                ['company_id' => $companyId, 'name' => $typeName ?: 'كفالات'],
                ['category' => 'advances', 'status' => 'active']
            );

            // 6. Prevent duplicates: same employee + amount + date + type + description
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

            // 7. Create installment
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

    protected function resolveOperator(string $operatorName): ?int
    {
        if (empty($operatorName)) {
            return null;
        }

        return EmployeeNameMatcher::find($operatorName)?->id;
    }
}
