<?php

namespace App\Services\AI\Tools;

use App\Enums\FundGeneralStatus;
use App\Models\Company;
use App\Models\LoanFund;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateLoanFundTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_loan_fund';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج وتأسيس صندوق قروض وسلف مالية جديد في المنشأة.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'fund_name' => [
                        'type' => 'STRING',
                        'description' => 'اسم صندوق القروض والسلف (مثال: صندوق التكافل الداخلي، صندوق سلف الموظفين)'
                    ],
                    'target_amount' => [
                        'type' => 'NUMBER',
                        'description' => 'المبلغ المستهدف للصندوق بالريال السعودي'
                    ],
                    'max_loan_limit' => [
                        'type' => 'NUMBER',
                        'description' => 'الحد الأقصى لمبلغ القرض الواحد بالريال السعودي'
                    ],
                    'max_repayment_months' => [
                        'type' => 'INTEGER',
                        'description' => 'الحد الأقصى لأشهر السداد (الافتراضي 12)'
                    ],
                    'trustee_name' => [
                        'type' => 'STRING',
                        'description' => 'اسم أمين أو مسؤول الصندوق'
                    ],
                    'rules_and_conditions' => [
                        'type' => 'STRING',
                        'description' => 'شروط ولوائح الصندوق'
                    ]
                ],
                'required' => ['fund_name']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $name = trim($arguments['fund_name'] ?? '');
            if (empty($name)) {
                return ['success' => false, 'message' => 'اسم صندوق القروض مطلوب.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $duplicate = LoanFund::where('company_id', $companyId)
                ->where('fund_name', $name)
                ->first();

            if ($duplicate) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): صندوق القروض ({$name}) مسجل مسبقاً برقم #{$duplicate->fund_number}، تم تخطيه لمنع التكرار.",
                ];
            }

            $fund = LoanFund::create([
                'company_id'           => $companyId,
                'fund_name'            => $name,
                'target_amount'        => (float)($arguments['target_amount'] ?? 0),
                'max_loan_limit'       => (float)($arguments['max_loan_limit'] ?? 0),
                'max_repayment_months' => (int)($arguments['max_repayment_months'] ?? 12),
                'trustee_name'         => $arguments['trustee_name'] ?? null,
                'rules_and_conditions' => $arguments['rules_and_conditions'] ?? null,
                'status'               => FundGeneralStatus::ACTIVE,
                'created_by'           => auth()->id(),
            ]);

            return [
                'success' => true,
                'message' => "تم إنشاء صندوق القروض ({$name}) بنجاح برقم #{$fund->fund_number}.",
                'fund_id' => $fund->id,
                'name'    => $name,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ صندوق القروض: ' . $e->getMessage(),
            ];
        }
    }
}
