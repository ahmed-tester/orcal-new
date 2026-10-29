<?php

namespace App\Services\AI\Tools;

use App\Enums\InstallmentPaymentSource;
use App\Models\Employee;
use App\Models\Installment;
use App\Models\InstallmentPayment;
use App\Services\AI\Contracts\GeminiToolInterface;
use App\Services\AI\EmployeeNameMatcher;
use App\Services\CompanyFormService;

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
            'description' => 'تسجيل سند سداد أو دفعة مسددة لقسط/كفالة/سلفة قائمة لموظف. تنبيه حاسم: يتم استدعاؤها حصراً عندما يكون هناك مبلغ مسدد أكبر من الصفر في عمود (المبلغ المسدد) أو إذا كان البيان (تسديد أقساط / سداد / دفعة). لا تستدعِ دالة create_installment_record لهذه السطور إطلاقاً.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'employee_identifier' => [
                        'type' => 'STRING',
                        'description' => 'اسم الموظف كما هو في الكشف (إذا كان ثنائي اللغة بالعربي والإنجليزي مثل "محمد حسين MOHAMMAD HOSSAIN" استخرجه كاملاً باللغتين دون حذف أي جزء)، أو رقمه الوظيفي أو رقم الهوية/الإقامة',
                    ],
                    'employee_name_ar' => [
                        'type' => 'STRING',
                        'description' => 'اسم الموظف باللغة العربية إن وجد في الكشف (مثال: محمد حسين)',
                    ],
                    'employee_name_en' => [
                        'type' => 'STRING',
                        'description' => 'اسم الموظف باللغة الإنجليزية إن وجد في الكشف (مثال: MOHAMMAD HOSSAIN)',
                    ],
                    'amount' => [
                        'type' => 'NUMBER',
                        'description' => 'المبلغ المسدد بالريال السعودي',
                    ],
                    'payment_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ السداد المكتوب في عمود (التاريخ) بالكشف بصيغة YYYY-MM-DD',
                    ],
                    'date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ الحركة المكتوب في عمود (التاريخ) بالكشف بصيغة YYYY-MM-DD',
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
            $identifier = trim($arguments['employee_identifier'] ?? $arguments['name'] ?? '');
            $explicitNameAr = trim($arguments['employee_name_ar'] ?? $arguments['name_ar'] ?? '');
            $explicitNameEn = trim($arguments['employee_name_en'] ?? $arguments['name_en'] ?? '');

            if ($explicitNameAr !== '' && ! str_contains($identifier, $explicitNameAr)) {
                $identifier = trim($identifier.' '.$explicitNameAr);
            }
            if ($explicitNameEn !== '' && ! str_contains(strtolower($identifier), strtolower($explicitNameEn))) {
                $identifier = trim($identifier.' '.$explicitNameEn);
            }

            $amount = (float) ($arguments['amount'] ?? 0);

            // Resolve date from any parameter provided by AI, and normalize to YYYY-MM-DD
            $rawDate = trim((string) ($arguments['payment_date'] ?? $arguments['date'] ?? $arguments['transaction_date'] ?? $arguments['installment_date'] ?? ''));
            if ($rawDate !== '') {
                try {
                    $date = \Carbon\Carbon::parse($rawDate)->format('Y-m-d');
                } catch (\Throwable $e) {
                    $date = $rawDate;
                }
            } else {
                $date = date('Y-m-d');
            }

            $rawInstallmentDate = trim((string) ($arguments['installment_date'] ?? ''));
            $installmentDate = '';
            if ($rawInstallmentDate !== '') {
                try {
                    $installmentDate = \Carbon\Carbon::parse($rawInstallmentDate)->format('Y-m-d');
                } catch (\Throwable $e) {
                    $installmentDate = $rawInstallmentDate;
                }
            }

            $installmentNumber = trim((string) ($arguments['installment_number'] ?? ''));
            $companyName = trim($arguments['company_name'] ?? '');
            $operatorName = trim($arguments['operator_name'] ?? $arguments['executed_by'] ?? '');
            $desc = trim($arguments['description'] ?? '');

            // Guard: If the description is an installment charge (like "كفالات 11") without payment intent
            if (preg_match('/^(وذلك عن\s+)?كفالات(\s+\d+)?$/u', $desc) && ! str_contains($desc, 'تسديد') && ! str_contains($desc, 'سداد') && ! str_contains($desc, 'دفع')) {
                return [
                    'success' => false,
                    'message' => 'تم تخطي السطر لأنه قسط كفالات وليس سداداً (البيان يشير لقسط/كفالة بدون تسديد).',
                ];
            }

            if (empty($identifier) && empty($explicitNameAr) && empty($explicitNameEn)) {
                return ['success' => false, 'message' => 'اسم أو معرف الموظف مطلوب لتسجيل السداد.'];
            }

            if ($amount <= 0) {
                return ['success' => false, 'message' => 'تم تخطي السطر لأن المبلغ المسدد يساوي صفراً أو غير محدد.'];
            }

            // 1. Resolve or create employee
            $employee = EmployeeNameMatcher::findOrCreate(
                $identifier,
                $arguments['company_id'] ?? null,
                $companyName,
                $explicitNameAr !== '' ? $explicitNameAr : null,
                $explicitNameEn !== '' ? $explicitNameEn : null
            );

            // 2. Resolve the single target installment — one statement line = one voucher (no splitting)
            $target = $this->resolveTargetInstallment($employee, $installmentNumber, $installmentDate);

            if (! $target) {
                return [
                    'success' => false,
                    'message' => __('smart_import.service.installment_not_found', [
                        'name' => $employee->user?->name ?? $identifier,
                        'date' => $installmentDate !== '' ? $installmentDate : $date,
                    ]),
                ];
            }

            // 3. Resolve operator
            $executedById = $this->resolveOperator($operatorName);

            // 4. Duplicate guard: Date is primary filter. If same day -> check amount and description
            $duplicateQuery = InstallmentPayment::where('employee_id', $employee->id)
                ->whereDate('payment_date', $date)
                ->where('amount', $amount);

            if ($desc !== '') {
                $duplicateQuery->where(function ($q) use ($desc) {
                    $q->where('description', $desc)
                        ->orWhereNull('description')
                        ->orWhere('description', '');
                });
            }

            $duplicate = $duplicateQuery->first();

            if ($duplicate) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): تم تخطي سند السداد للموظف ({$employee->user?->name}) بمبلغ ".number_format($amount, 2)." ر.س لأنه مسجل مسبقاً (سند #{$duplicate->voucher_number}) لمنع الازدواجية.",
                ];
            }

            // 5. Record one voucher for the full amount on the target installment
            $remainingBefore = (float) $target->remaining_amount;
            $overpaidAmount = round(max(0, $amount - $remainingBefore), 2);

            $payment = InstallmentPayment::create([
                'company_id' => $target->company_id
                    ?: $this->resolveCompanyId($arguments['company_id'] ?? null, $companyName, $employee),
                'employee_id' => $employee->id,
                'installment_id' => $target->id,
                'payment_type' => $target->transaction_type ?? 'installment',
                'payment_date' => $date,
                'amount' => $amount,
                'payment_source' => InstallmentPaymentSource::CASH,
                'executed_by' => $executedById ?: auth()->user()?->employee?->id,
                'description' => $desc !== '' ? $desc : "سداد قسط للموظف {$employee->user?->name}",
                'metadata' => $overpaidAmount > 0
                    ? [
                        'overpaid' => true,
                        'overpaid_amount' => $overpaidAmount,
                        'remaining_before' => $remainingBefore,
                    ]
                    : [],
            ]);

            $message = "تم تسجيل سند سداد بنجاح للموظف ({$employee->user?->name}) بمبلغ ".number_format($amount, 2)." ر.س (سند #{$payment->voucher_number})";

            if ($overpaidAmount > 0) {
                $message .= ' — تنبيه: المبلغ أكبر من المتبقي على القسط بمقدار '.number_format($overpaidAmount, 2).' ر.س وتم تسجيله كزيادة.';
            }

            return [
                'success' => true,
                'message' => $message,
                'record_name' => $employee->user?->name ?? "سند #{$payment->voucher_number}",
                'details' => [
                    'amount' => number_format($amount, 2).' ر.س',
                    'voucher_number' => $payment->voucher_number,
                    'installment_number' => $target->installment_number,
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
     * Resolve the single installment a statement line belongs to.
     * One statement line = one installment (never split across installments).
     */
    protected function resolveTargetInstallment(Employee $employee, string $installmentNumber, string $installmentDate): ?Installment
    {
        $query = fn () => Installment::where('employee_id', $employee->id);

        if ($installmentNumber !== '') {
            $byNumber = $query()->where('installment_number', $installmentNumber)->first();

            if ($byNumber) {
                return $byNumber;
            }
        }

        if ($installmentDate !== '') {
            $byDate = $query()->whereDate('installment_date', $installmentDate)->orderByDesc('id')->first();

            if ($byDate) {
                return $byDate;
            }

            // Same-month fallback: statements sometimes carry a different day for the same due installment
            if (preg_match('/^(\d{4})-(\d{2})/', $installmentDate, $matches)) {
                $byMonth = $query()
                    ->whereYear('installment_date', (int) $matches[1])
                    ->whereMonth('installment_date', (int) $matches[2])
                    ->orderByDesc('id')
                    ->first();

                if ($byMonth) {
                    return $byMonth;
                }
            }
        }

        return $query()
            ->where('remaining_amount', '>', 0)
            ->orderBy('installment_date')
            ->orderBy('id')
            ->first()
            ?? $query()->orderByDesc('installment_date')->orderByDesc('id')->first();
    }

    /**
     * Resolve the target company: explicit selection > exact/partial name match >
     * the employee's own company. Companies are never created from a file here.
     */
    /**
     * Company fallback for payments: never leaves the row companyless —
     * resolves by name and creates the company if it does not exist yet.
     */
    protected function resolveCompanyId(mixed $explicitCompanyId, string $companyName, Employee $employee): ?int
    {
        if ($companyName !== '') {
            $companyRes = CompanyFormService::resolveOrCreateCompanyByName(
                $companyName,
                ! empty($explicitCompanyId) ? (int) $explicitCompanyId : null
            );

            if (! empty($companyRes['company_id'])) {
                return (int) $companyRes['company_id'];
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
