<?php

namespace App\Services\AI\Tools;

use App\Models\Company;
use App\Models\RoscaFund;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateRoscaFundTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_rosca_fund';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج وتأسيس جمعية مالية (ROSCA) جديدة وتحديد القسط الشهري وعدد المشتركين.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'fund_name' => [
                        'type' => 'STRING',
                        'description' => 'اسم الجمعية المالية (مثال: جمعية الموظفين 2026، جمعية شراء سيارة)'
                    ],
                    'monthly_installment' => [
                        'type' => 'NUMBER',
                        'description' => 'قيمة السهم أو القسط الشهري للعضو بالريال السعودي'
                    ],
                    'members_count' => [
                        'type' => 'INTEGER',
                        'description' => 'عدد المشتركين أو الأشهر المقررة'
                    ],
                    'start_date' => [
                        'type' => 'STRING',
                        'description' => 'تاريخ بداية الجمعية بصيغة YYYY-MM-DD'
                    ],
                    'manager_name' => [
                        'type' => 'STRING',
                        'description' => 'اسم مدير أو مسؤول الجمعية'
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
                return ['success' => false, 'message' => 'اسم الجمعية المالية مطلوب.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $duplicate = RoscaFund::where('company_id', $companyId)
                ->where('fund_name', $name)
                ->first();

            if ($duplicate) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): الجمعية المالية ({$name}) مسجلة مسبقاً برقم #{$duplicate->fund_number}، تم تخطيها لمنع التكرار.",
                ];
            }

            $monthly = (float)($arguments['monthly_installment'] ?? 0);
            $count = (int)($arguments['members_count'] ?? 10);
            $totalPool = $monthly * $count;
            $startDate = !empty($arguments['start_date']) ? $arguments['start_date'] : date('Y-m-d');

            $fund = RoscaFund::create([
                'company_id'          => $companyId,
                'fund_name'           => $name,
                'monthly_installment' => $monthly,
                'members_count'       => $count,
                'total_pool_amount'   => $totalPool,
                'start_date'          => $startDate,
                'manager_name'        => $arguments['manager_name'] ?? null,
                'status'              => 'active',
            ]);

            return [
                'success' => true,
                'message' => "تم تأسيس الجمعية المالية ({$name}) بنجاح برقم #{$fund->fund_number}.",
                'fund_id' => $fund->id,
                'name'    => $name,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ الجمعية المالية: ' . $e->getMessage(),
            ];
        }
    }
}
