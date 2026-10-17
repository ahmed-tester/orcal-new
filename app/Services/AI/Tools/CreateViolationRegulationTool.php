<?php

namespace App\Services\AI\Tools;

use App\Models\ViolationRegulation;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateViolationRegulationTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_violation_regulation';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج مادة أو بند من لائحة وأنظمة المخالفات والجزاءات المعتمدة.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'title' => [
                        'type' => 'STRING',
                        'description' => 'عنوان أو رقم المادة في اللائحة (مثال: المادة الرابعة: مخالفات السلامة)'
                    ],
                    'content' => [
                        'type' => 'STRING',
                        'description' => 'نص المادة أو البند النظامي'
                    ]
                ],
                'required' => ['title', 'content']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $title = trim($arguments['title'] ?? '');
            $content = trim($arguments['content'] ?? '');

            if (empty($title) || empty($content)) {
                return ['success' => false, 'message' => 'عنوان اللائحة ونص البند مطلوبان.'];
            }

            $duplicate = ViolationRegulation::where('title', $title)->first();
            if ($duplicate) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): مادة اللائحة ({$title}) مسجلة مسبقاً، تم تخطيها لمنع التكرار.",
                ];
            }

            $reg = ViolationRegulation::create([
                'title'     => $title,
                'content'   => $content,
                'is_active' => true,
            ]);

            return [
                'success'       => true,
                'message'       => "تم تسجيل بند اللائحة ({$title}) بنجاح.",
                'regulation_id' => $reg->id,
                'name'          => $title,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ مادة اللائحة: ' . $e->getMessage(),
            ];
        }
    }
}
