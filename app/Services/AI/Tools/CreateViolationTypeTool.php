<?php

namespace App\Services\AI\Tools;

use App\Models\ViolationCategory;
use App\Models\ViolationType;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateViolationTypeTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_violation_type';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج نوع أو مسمى مخالفة فرعي جديد مع ربطه بالقسم المناسب.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => [
                        'type' => 'STRING',
                        'description' => 'اسم أو مسمى نوع المخالفة (مثال: استخدام الهاتف أثناء القيادة، التأخر عن الوردية)'
                    ],
                    'category_name' => [
                        'type' => 'STRING',
                        'description' => 'اسم قسم المخالفات التابع له'
                    ],
                    'description' => [
                        'type' => 'STRING',
                        'description' => 'وصف تفصيلي للمخالفة'
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
                return ['success' => false, 'message' => 'اسم نوع المخالفة مطلوب.'];
            }

            $categoryId = null;
            if (!empty($arguments['category_name'])) {
                $catName = trim($arguments['category_name']);
                $cat = ViolationCategory::where('name', 'like', "%{$catName}%")->first();
                if (!$cat) {
                    $cat = ViolationCategory::create(['name' => $catName, 'is_active' => true]);
                }
                $categoryId = $cat->id;
            } else {
                $categoryId = ViolationCategory::first()?->id ?? ViolationCategory::create(['name' => 'عام', 'is_active' => true])->id;
            }

            $duplicate = ViolationType::where('name', $name)->first();
            if ($duplicate) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): نوع المخالفة ({$name}) مسجل مسبقاً، تم تخطيه لمنع التكرار.",
                ];
            }

            $type = ViolationType::create([
                'violation_category_id' => $categoryId,
                'name'                  => $name,
                'description'           => $arguments['description'] ?? null,
                'is_active'             => true,
            ]);

            return [
                'success' => true,
                'message' => "تم إضافة نوع المخالفة ({$name}) بنجاح.",
                'type_id' => $type->id,
                'name'    => $name,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ نوع المخالفة: ' . $e->getMessage(),
            ];
        }
    }
}
