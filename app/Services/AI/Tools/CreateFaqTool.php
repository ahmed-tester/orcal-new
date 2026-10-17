<?php

namespace App\Services\AI\Tools;

use App\Models\Faq;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateFaqTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_faq_record';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج سؤال وجواب في قائمة الأسئلة الشائعة وتسهيل وصول الموظفين والعملاء للإجابات.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'question' => [
                        'type' => 'STRING',
                        'description' => 'نص السؤال'
                    ],
                    'answer' => [
                        'type' => 'STRING',
                        'description' => 'نص الإجابة التفصيلية'
                    ]
                ],
                'required' => ['question', 'answer']
            ]
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $question = trim($arguments['question'] ?? '');
            $answer = trim($arguments['answer'] ?? '');

            if (empty($question) || empty($answer)) {
                return ['success' => false, 'message' => 'السؤال والإجابة مطلوبان.'];
            }

            $existing = Faq::where('question', $question)->first();
            if ($existing) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): السؤال ({$question}) مسجل مسبقاً، تم تخطيه.",
                ];
            }

            Faq::create([
                'question' => $question,
                'answer' => $answer,
                'is_active' => true,
                'sort_order' => 0,
            ]);

            return [
                'success' => true,
                'message' => "تم إضافة السؤال الشائع بنجاح: ({$question})",
                'record_name' => $question,
                'details' => [
                    'question' => $question,
                ]
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ السؤال الشائع: ' . $e->getMessage(),
            ];
        }
    }
}
