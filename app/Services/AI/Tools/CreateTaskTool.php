<?php

namespace App\Services\AI\Tools;

use App\Enums\GeneralStatus;
use App\Models\Task;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateTaskTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_task_record';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج مهمة وظيفية أو نشاط عمل في قائمة المهام مع تحديد الوزن التقديري والوصف.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => [
                        'type' => 'STRING',
                        'description' => 'اسم المهمة أو النشاط'
                    ],
                    'default_weight' => [
                        'type' => 'NUMBER',
                        'description' => 'الوزن أو التقييم النسبي للمهمة (الافتراضي 1)'
                    ],
                    'description' => [
                        'type' => 'STRING',
                        'description' => 'شرح أو تفاصيل تنفيذ المهمة'
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
            $weight = (float)($arguments['default_weight'] ?? 1);
            $description = trim($arguments['description'] ?? '');

            if (empty($name)) {
                return ['success' => false, 'message' => 'اسم المهمة مطلوب.'];
            }

            // فحص التكرار بالاسم
            $existing = Task::where('name', $name)->first();
            if ($existing) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): المهمة ({$name}) مسجلة مسبقاً بكود #{$existing->task_code}، تم تخطيها.",
                ];
            }

            $task = Task::create([
                'name' => $name,
                'default_weight' => $weight > 0 ? $weight : 1,
                'description' => $description ?: 'استيراد ذكي — مهمة عمل',
                'status' => GeneralStatus::ACTIVE,
            ]);

            return [
                'success' => true,
                'message' => "تم تسجيل المهمة ({$name}) بنجاح بكود #{$task->task_code}",
                'record_name' => $name,
                'details' => [
                    'task_code' => $task->task_code,
                    'weight' => $task->default_weight,
                ]
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ المهمة: ' . $e->getMessage(),
            ];
        }
    }
}
