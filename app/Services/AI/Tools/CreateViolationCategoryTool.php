<?php

namespace App\Services\AI\Tools;

use App\Models\ViolationCategory;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateViolationCategoryTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_violation_category';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج قسم أو تصنيف رئيسي للمخالفات والجزاءات مع منع التكرار.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => [
                        'type' => 'STRING',
                        'description' => 'اسم قسم المخالفة بالعربية (مثال: مخالفات مرورية، مخالفات سلوكية، مخالفات إدارية)'
                    ],
                    'name_en' => [
                        'type' => 'STRING',
                        'description' => 'اسم القسم بالإنجليزية (اختياري)'
                    ],
                    'description' => [
                        'type' => 'STRING',
                        'description' => 'وصف تفصيلي للقسم'
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
                return ['success' => false, 'message' => 'اسم قسم المخالفة مطلوب.'];
            }

            $duplicate = ViolationCategory::where('name', $name)->first();
            if ($duplicate) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): قسم المخالفات ({$name}) مسجل مسبقاً، تم تخطيه لمنع التكرار.",
                ];
            }

            $cat = ViolationCategory::create([
                'name'        => $name,
                'name_en'     => $arguments['name_en'] ?? null,
                'description' => $arguments['description'] ?? null,
                'is_active'   => true,
            ]);

            return [
                'success'     => true,
                'message'     => "تم إضافة قسم المخالفات ({$name}) بنجاح.",
                'category_id' => $cat->id,
                'name'        => $name,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ قسم المخالفات: ' . $e->getMessage(),
            ];
        }
    }
}
