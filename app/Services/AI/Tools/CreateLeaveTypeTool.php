<?php

namespace App\Services\AI\Tools;

use App\Enums\GeneralStatus;
use App\Models\Company;
use App\Models\LeaveType;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateLeaveTypeTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_leave_type';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج نوع إجازة جديد في لائحة الموارد البشرية (مثال: إجازة سنوية، إجازة مرضية، إجازة طارئة، إجازة وفاة).',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => [
                        'type' => 'STRING',
                        'description' => 'اسم أو مسمى نوع الإجازة'
                    ],
                    'annual_balance' => [
                        'type' => 'INTEGER',
                        'description' => 'الرصيد السنوي المسموح بالأيام (مثال: 30، 21، 5)'
                    ],
                    'is_paid' => [
                        'type' => 'BOOLEAN',
                        'description' => 'هل الإجازة مدفوعة الأجر (true/false - الافتراضي true)'
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
                return ['success' => false, 'message' => 'اسم نوع الإجازة مطلوب.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $duplicate = LeaveType::where('company_id', $companyId)
                ->where('name', $name)
                ->first();

            if ($duplicate) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): نوع الإجازة ({$name}) مسجل مسبقاً برصيد ({$duplicate->annual_balance}) يوم، تم تخطيه لمنع التكرار.",
                ];
            }

            $type = LeaveType::create([
                'company_id'        => $companyId,
                'name'              => $name,
                'annual_balance'    => (int)($arguments['annual_balance'] ?? 0),
                'is_paid'           => (bool)($arguments['is_paid'] ?? true),
                'requires_approval' => true,
                'status'            => GeneralStatus::ACTIVE,
            ]);

            return [
                'success' => true,
                'message' => "تم إضافة نوع الإجازة ({$name}) برصيد ({$type->annual_balance}) يوم بنجاح.",
                'type_id' => $type->id,
                'name'    => $name,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'تعذر حفظ نوع الإجازة: ' . $e->getMessage(),
            ];
        }
    }
}
