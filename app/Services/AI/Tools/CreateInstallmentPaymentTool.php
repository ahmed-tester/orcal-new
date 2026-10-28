<?php

namespace App\Services\AI\Tools;

use App\Enums\InstallmentPaymentSource;
use App\Enums\InstallmentStatus;
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
            'description' => 'تسجيل سند سداد أو دفعة مسددة لقسط/كفالة/سلفة قائمة لموظف. يتم استدعاؤها فقط عندما يكون هناك مبلغ مسدد أكبر من الصفر (المبلغ المسدد > 0). لا تستدعِها إذا كان المبلغ 0 أو فارغاً. إذا لم يكن الموظف مسجلاً يتم إنشاؤه تلقائياً، ولكن يجب أن يكون له قسط مسجل مسبقاً لربط السداد به.',
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
            $date = trim($arguments['payment_date'] ?? date('Y-m-d'));
            $installmentDate = trim($arguments['installment_date'] ?? '');
            $installmentNumber = trim((string) ($arguments['installment_number'] ?? ''));
            $companyName = trim($arguments['company_name'] ?? '');
            $operatorName = trim($arguments['operator_name'] ?? $arguments['executed_by'] ?? '');
            $desc = trim($arguments['description'] ?? '');

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

            // 2. Resolve candidates to pay against (specific installment or FIFO waterfall over open installments)
            if ($installmentNumber !== '') {
                $target = Installment::where('employee_id', $employee->id)
                    ->where('installment_number', $installmentNumber)
                    ->first();
                $candidates = $target ? collect([$target]) : collect();
            } else {
                // Fetch open installments with remaining balance, prioritizing explicit installmentDate, then oldest first (FIFO)
                $candidates = Installment::where('employee_id', $employee->id)
                    ->where('remaining_amount', '>', 0)
                    ->when($installmentDate !== '', function ($q) use ($installmentDate) {
                        $q->orderByRaw('CASE WHEN installment_date = ? THEN 0 ELSE 1 END', [$installmentDate]);
                    })
                    ->orderBy('installment_date', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                if ($candidates->isEmpty()) {
                    // Fallback to latest installment if all are closed or overpaid
                    $fallback = Installment::where('employee_id', $employee->id)
                        ->when($installmentDate !== '', fn ($q) => $q->orderByRaw('CASE WHEN installment_date = ? THEN 0 ELSE 1 END', [$installmentDate]))
                        ->orderByDesc('installment_date')
                        ->orderByDesc('id')
                        ->first();
                    $candidates = $fallback ? collect([$fallback]) : collect();
                }
            }

            if ($candidates->isEmpty()) {
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

            // 4. Distribute payment across candidates (FIFO Waterfall)
            $remainingToPay = $amount;
            $paymentsCreated = [];
            $alreadyRecorded = 0;

            foreach ($candidates as $idx => $inst) {
                if ($remainingToPay <= 0) {
                    break;
                }

                $isLast = ($idx === $candidates->count() - 1);
                $remOnInst = (float) $inst->remaining_amount;
                $portion = ($isLast || $remOnInst <= 0) ? $remainingToPay : min($remainingToPay, $remOnInst);

                if ($portion <= 0) {
                    continue;
                }

                $duplicateQuery = InstallmentPayment::where('installment_id', $inst->id)
                    ->where('amount', $portion)
                    ->whereDate('payment_date', $date);

                if ($desc !== '') {
                    $duplicateQuery->where('description', $desc);
                }

                $duplicate = $duplicateQuery->first();
                if ($duplicate) {
                    $alreadyRecorded++;
                    $remainingToPay -= $portion;

                    continue;
                }

                $companyId = $inst->company_id
                    ?: $this->resolveCompanyId($arguments['company_id'] ?? null, $companyName, $employee);

                $payment = InstallmentPayment::create([
                    'company_id' => $companyId,
                    'employee_id' => $employee->id,
                    'installment_id' => $inst->id,
                    'payment_type' => $inst->transaction_type ?? 'installment',
                    'payment_date' => $date,
                    'amount' => $portion,
                    'payment_source' => InstallmentPaymentSource::CASH,
                    'executed_by' => $executedById ?: auth()->user()?->employee?->id,
                    'description' => $desc !== '' ? $desc : "سداد قسط للموظف {$employee->user?->name}",
                ]);

                $paymentsCreated[] = $payment;
                $remainingToPay -= $portion;
            }

            if (empty($paymentsCreated)) {
                if ($alreadyRecorded > 0) {
                    return [
                        'success' => false,
                        'message' => "تنبيه (مكرر): تم تخطي سند السداد للموظف ({$employee->user?->name}) بمبلغ ".number_format($amount, 2).' ر.س لأنه مسجل مسبقاً لمنع الازدواجية.',
                    ];
                }

                return [
                    'success' => false,
                    'message' => "تعذر توزيع مبلغ السداد للموظف ({$employee->user?->name}).",
                ];
            }

            $vouchers = collect($paymentsCreated)->pluck('voucher_number')->filter()->implode(', ');
            $instNumbers = collect($paymentsCreated)->map(fn ($p) => $p->installment?->installment_number)->filter()->unique()->implode(', ');

            return [
                'success' => true,
                'message' => "تم تسجيل سند سداد بنجاح للموظف ({$employee->user?->name}) بمبلغ ".number_format($amount, 2)." ر.س (سند #{$vouchers})",
                'record_name' => $employee->user?->name ?? "سند #{$vouchers}",
                'details' => [
                    'amount' => number_format($amount, 2).' ر.س',
                    'voucher_number' => $vouchers,
                    'installment_number' => $instNumbers,
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
