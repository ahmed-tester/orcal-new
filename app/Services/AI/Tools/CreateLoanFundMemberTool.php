<?php

namespace App\Services\AI\Tools;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LoanFund;
use App\Models\LoanFundMember;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateLoanFundMemberTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_loan_fund_member';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج مشترك أو عضو جديد في صندوق القروض والسلف مع تحديد القسط الشهري وعدد الأقساط.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'full_name' => [
                        'type' => 'STRING',
                        'description' => 'اسم المشترك الكامل أو اسم الموظف'
                    ],
                    'fund_name' => [
                        'type' => 'STRING',
                        'description' => 'اسم صندوق القروض (اختياري)'
                    ],
                    'phone' => [
                        'type' => 'STRING',
                        'description' => 'رقم جوال المشترك'
                    ],
                    'national_id' => [
                        'type' => 'STRING',
                        'description' => 'رقم الهوية أو الإقامة'
                    ],
                    'monthly_installment' => [
                        'type' => 'NUMBER',
                        'description' => 'مبلغ الاشتراك أو القسط الشهري بالريال السعودي'
                    ],
                    'installments_count' => [
                        'type' => 'INTEGER',
                        'description' => 'عدد أشهر الاشتراك (الافتراضي 12)'
                    ],
                    'paid_amount' => [
                        'type' => 'NUMBER',
                        'description' => 'إجمالي المسدد حتى الآن إن وجد'
                    ]
                ],
                'required' => ['full_name', 'monthly_installment']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $name = trim($arguments['full_name'] ?? '');
            $fundName = trim($arguments['fund_name'] ?? '');
            $phone = trim($arguments['phone'] ?? '');
            $nationalId = trim($arguments['national_id'] ?? '');
            $monthly = (float)($arguments['monthly_installment'] ?? 0);
            $count = max(1, (int)($arguments['installments_count'] ?? 12));
            $paid = (float)($arguments['paid_amount'] ?? 0);

            if (empty($name)) {
                return ['success' => false, 'message' => 'اسم المشترك مطلوب.'];
            }

            if ($monthly <= 0) {
                return ['success' => false, 'message' => 'القسط الشهري يجب أن يكون أكبر من الصفر.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            // العثور على الصندوق أو إنشاؤه
            $fund = null;
            if (!empty($fundName)) {
                $fund = LoanFund::where('company_id', $companyId)->where('fund_name', $fundName)->first();
            }
            if (!$fund) {
                $fund = LoanFund::firstOrCreate(
                    ['company_id' => $companyId, 'fund_name' => $fundName ?: 'صندوق القروض والسلف العام'],
                    ['status' => 'active', 'default_installments_count' => 12, 'target_amount' => 100000, 'max_loan_limit' => 20000]
                );
            }

            // فحص هل المشترك موظف بالنظام
            $employee = Employee::where('company_id', $companyId)
                ->whereHas('user', fn($q) => $q->where('name', 'like', "%{$name}%"))
                ->first();

            // منع التكرار
            $existing = LoanFundMember::where('loan_fund_id', $fund->id)
                ->where(function ($q) use ($name, $nationalId, $employee) {
                    $q->where('full_name', $name);
                    if ($nationalId) $q->orWhere('national_id', $nationalId);
                    if ($employee) $q->orWhere('employee_id', $employee->id);
                })
                ->first();

            if ($existing) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): المشترك ({$name}) مسجل مسبقاً في ({$fund->fund_name}) برقم #{$existing->membership_number}، تم تخطيه.",
                ];
            }

            $member = LoanFundMember::create([
                'loan_fund_id' => $fund->id,
                'member_type' => $employee ? 'employee' : 'external',
                'employee_id' => $employee?->id,
                'full_name' => $name,
                'phone' => $phone ?: ($employee?->user?->phone),
                'national_id' => $nationalId ?: ($employee?->saudiDetail?->national_id ?? $employee?->foreignDetail?->iqama_number),
                'monthly_installment' => $monthly,
                'installments_count' => $count,
                'paid_amount' => $paid,
                'joined_date' => date('Y-m-d'),
                'status' => 'active',
            ]);

            return [
                'success' => true,
                'message' => "تم تسجيل المشترك ({$name}) بنجاح في ({$fund->fund_name}) بقسط شهري " . number_format($monthly, 2) . " ر.س",
                'record_name' => $name,
                'details' => [
                    'fund' => $fund->fund_name,
                    'monthly_installment' => number_format($monthly, 2) . ' ر.س',
                    'total' => number_format($member->total_annual_amount, 2) . ' ر.س',
                ]
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ المشترك: ' . $e->getMessage(),
            ];
        }
    }
}
