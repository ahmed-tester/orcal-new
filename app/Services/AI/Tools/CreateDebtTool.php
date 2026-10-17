<?php

namespace App\Services\AI\Tools;

use App\Models\Company;
use App\Models\Debt;
use App\Models\Employee;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateDebtTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_debt_record';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج مديونية أو التزام مالي جديد على موظف داخلي أو جهة خارجية مع تحديد المبلغ وتاريخ الاستحقاق.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'beneficiary_name' => [
                        'type' => 'STRING',
                        'description' => 'اسم المدين / المستفيد (اسم الموظف أو العميل أو الجهة الخارجية)'
                    ],
                    'total_amount' => [
                        'type' => 'NUMBER',
                        'description' => 'إجمالي مبلغ الدين بالريال السعودي'
                    ],
                    'debt_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ نشوء الدين بصيغة YYYY-MM-DD (الافتراضي اليوم)'
                    ],
                    'due_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ استحقاق سداد الدين بصيغة YYYY-MM-DD'
                    ],
                    'phone' => [
                        'type' => 'STRING',
                        'description' => 'رقم جوال المدين'
                    ],
                    'national_id' => [
                        'type' => 'STRING',
                        'description' => 'رقم الهوية الوطنية أو الإقامة أو السجل'
                    ],
                    'debt_reason' => [
                        'type' => 'STRING',
                        'description' => 'سبب أو نوع المديونية'
                    ],
                    'notes' => [
                        'type' => 'STRING',
                        'description' => 'ملاحظات إضافية'
                    ]
                ],
                'required' => ['beneficiary_name', 'total_amount']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $name = trim($arguments['beneficiary_name'] ?? '');
            $amount = (float)($arguments['total_amount'] ?? 0);

            if (empty($name) || $amount <= 0) {
                return ['success' => false, 'message' => 'اسم المدين ومبلغ الدين مطلوبان.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            // فحص هل المدين موظف داخلي بالمنشأة
            $employee = Employee::where('company_id', $companyId)
                ->where(function ($q) use ($name, $arguments) {
                    $q->whereHas('user', fn($u) => $u->where('name', 'like', "%{$name}%"))
                      ->orWhere('employee_number', $name);
                    if (!empty($arguments['national_id'])) {
                        $q->orWhereHas('saudiDetail', fn($s) => $s->where('national_id', $arguments['national_id']))
                          ->orWhereHas('foreignDetail', fn($f) => $f->where('iqama_number', $arguments['national_id']));
                    }
                })->first();

            $debtDate = !empty($arguments['debt_date']) ? $arguments['debt_date'] : date('Y-m-d');
            $dueDate = !empty($arguments['due_date']) ? $arguments['due_date'] : null;

            if ($employee) {
                $debtorType = 'internal';
                $employeeId = $employee->id;
                $extName = null;
                $extPhone = null;
                $extId = null;
            } else {
                $debtorType = 'external';
                $employeeId = null;
                $extName = $name;
                $extPhone = $arguments['phone'] ?? null;
                $extId = $arguments['national_id'] ?? null;
            }

            // فحص منع التكرار
            $duplicate = Debt::where('company_id', $companyId)
                ->where('total_amount', $amount)
                ->whereDate('debt_date', $debtDate)
                ->where(function ($q) use ($employeeId, $extName) {
                    if ($employeeId) {
                        $q->where('employee_id', $employeeId);
                    } else {
                        $q->where('external_name', $extName);
                    }
                })->first();

            if ($duplicate) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): تم تسجيل دين سابق على ({$name}) بنفس المبلغ والتاريخ برقم #{$duplicate->debt_number}، تم تخطيه لمنع التكرار.",
                ];
            }

            $debt = Debt::create([
                'company_id'           => $companyId,
                'debtor_type'          => $debtorType,
                'employee_id'          => $employeeId,
                'external_name'        => $extName,
                'external_phone'       => $extPhone,
                'external_national_id' => $extId,
                'total_amount'         => $amount,
                'paid_amount'          => 0,
                'remaining_amount'     => $amount,
                'debt_date'            => $debtDate,
                'due_date'             => $dueDate,
                'debt_reason'          => $arguments['debt_reason'] ?? 'استيراد ذكي — قيد دين',
                'description'          => $arguments['notes'] ?? null,
                'status'               => 'unpaid',
            ]);

            return [
                'success' => true,
                'message' => "تم تسجيل دين جديد على ({$name}) بمبلغ (" . number_format($amount, 2) . " ر.س) بنجاح برقم #{$debt->debt_number}.",
                'debt_id' => $debt->id,
                'name'    => "دين #{$debt->debt_number} ({$name})",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ سجل الدين: ' . $e->getMessage(),
            ];
        }
    }
}
