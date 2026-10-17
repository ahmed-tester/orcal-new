<?php

namespace App\Services\AI\Tools;

use App\Enums\GeneralStatus;
use App\Enums\InstallmentCategory;
use App\Models\Company;
use App\Models\InstallmentType;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateInstallmentTypeTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_installment_type';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج نوع أو تصنيف قسط أو سلفة مالية جديد (مثال: قسط سلفة شخصية، قسط سيارة، قسط مديونية مشتريات).',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => [
                        'type' => 'STRING',
                        'description' => 'اسم أو مسمى نوع القسط'
                    ],
                    'category' => [
                        'type' => 'STRING',
                        'description' => 'تصنيف القسط: advances (سلف), debts (ديون), others (أخرى)'
                    ]
                ],
                'required' => ['name']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $name = trim($arguments['name'] ?? '');
            if (empty($name)) {
                return ['success' => false, 'message' => 'اسم نوع القسط مطلوب.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $catInput = strtolower(trim($arguments['category'] ?? 'advances'));
            $category = InstallmentCategory::tryFrom($catInput) ?? InstallmentCategory::ADVANCES;

            $duplicate = InstallmentType::where('company_id', $companyId)
                ->where('name', $name)
                ->first();

            if ($duplicate) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): نوع القسط ({$name}) مسجل مسبقاً، تم تخطيه لمنع التكرار.",
                ];
            }

            $type = InstallmentType::create([
                'company_id' => $companyId,
                'name'       => $name,
                'category'   => $category,
                'status'     => GeneralStatus::ACTIVE,
            ]);

            return [
                'success' => true,
                'message' => "تم إضافة نوع القسط ({$name}) بنجاح.",
                'type_id' => $type->id,
                'name'    => $name,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ نوع القسط: ' . $e->getMessage(),
            ];
        }
    }
}
