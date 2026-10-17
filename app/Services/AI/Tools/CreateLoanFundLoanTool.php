<?php

namespace App\Services\AI\Tools;

use App\Models\Company;
use App\Models\LoanFund;
use App\Models\LoanFundLoan;
use App\Models\LoanFundMember;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateLoanFundLoanTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_loan_fund_loan';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج قرض أو سلفة من صندوق القروض لمشترك مع جدولة الأقساط.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'member_identifier' => [
                        'type' => 'STRING',
                        'description' => 'اسم المشترك أو رقم عضويته أو هويته في صندوق القروض'
                    ],
                    'loan_amount' => [
                        'type' => 'NUMBER',
                        'description' => 'مبلغ القرض بالريال السعودي'
                    ],
                    'installments_count' => [
                        'type' => 'INTEGER',
                        'description' => 'عدد الأقساط الشهرية (مثال: 10، 12)'
                    ],
                    'monthly_installment' => [
                        'type' => 'NUMBER',
                        'description' => 'قيمة القسط الشهري (إذا لم يحدد يتم حسابه تلقائياً من إجمالي المبلغ / عدد الأقساط)'
                    ],
                    'loan_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ القرض بصيغة YYYY-MM-DD (الافتراضي اليوم)'
                    ],
                    'notes' => [
                        'type' => 'STRING',
                        'description' => 'ملاحظات القرض'
                    ]
                ],
                'required' => ['member_identifier', 'loan_amount']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $ident = trim($arguments['member_identifier'] ?? '');
            $amount = (float)($arguments['loan_amount'] ?? 0);

            if (empty($ident) || $amount <= 0) {
                return ['success' => false, 'message' => 'بيانات المشترك ومبلغ القرض مطلوبان.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $member = LoanFundMember::where(function ($q) use ($ident) {
                $q->where('id', $ident)
                  ->orWhere('member_number', $ident)
                  ->orWhere('member_name', 'like', "%{$ident}%")
                  ->orWhere('national_id', $ident);
            })->first();

            if (!$member) {
                return [
                    'success' => false,
                    'message' => "تعذر العثور على المشترك ({$ident}) في صناديق القروض.",
                ];
            }

            $fundId = $member->loan_fund_id ?? LoanFund::where('company_id', $companyId)->first()?->id ?? 1;
            $count = (int)($arguments['installments_count'] ?? 12);
            if ($count <= 0) $count = 12;

            $monthly = (float)($arguments['monthly_installment'] ?? 0);
            if ($monthly <= 0) {
                $monthly = round($amount / $count, 2);
            }

            $loanDate = !empty($arguments['loan_date']) ? $arguments['loan_date'] : date('Y-m-d');

            $loan = LoanFundLoan::create([
                'loan_fund_id'        => $fundId,
                'loan_fund_member_id' => $member->id,
                'loan_amount'         => $amount,
                'installments_count'  => $count,
                'monthly_installment' => $monthly,
                'paid_amount'         => 0,
                'remaining_amount'    => $amount,
                'loan_date'           => $loanDate,
                'start_due_date'      => date('Y-m-d', strtotime('+1 month', strtotime($loanDate))),
                'status'              => 'active',
                'notes'               => $arguments['notes'] ?? 'استيراد ذكي — قرض صندوق',
            ]);

            return [
                'success' => true,
                'message' => "تم تسجيل قرض بمبلغ (" . number_format($amount, 2) . " ر.س) للمشترك ({$member->member_name}) بنجاح برقم #{$loan->loan_number}.",
                'loan_id' => $loan->id,
                'name'    => "قرض #{$loan->loan_number} ({$member->member_name})",
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ قرض الصندوق: ' . $e->getMessage(),
            ];
        }
    }
}
