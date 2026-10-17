<?php

namespace App\Services\AI\Tools;

use App\Enums\GeneralStatus;
use App\Models\Company;
use App\Models\Department;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateDepartmentTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_department_record';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج قسم أو إدارة جديدة في الهيكل التنظيمي للمنشأة.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => [
                        'type' => 'STRING',
                        'description' => 'اسم القسم أو الإدارة (مثال: الموارد البشرية، المحاسبة والمالية، المبيعات)'
                    ],
                    'department_code' => [
                        'type' => 'STRING',
                        'description' => 'رمز القسم (اختياري)'
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
            $code = trim($arguments['department_code'] ?? '');

            if (empty($name)) {
                return ['success' => false, 'message' => 'اسم القسم مطلوب.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $existing = Department::where('company_id', $companyId)
                ->where('name', $name)
                ->first();

            if ($existing) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): القسم ({$name}) مسجل مسبقاً، تم تخطيه.",
                ];
            }

            $dept = Department::create([
                'company_id' => $companyId,
                'name' => $name,
                'department_code' => $code ?: null,
                'status' => GeneralStatus::ACTIVE,
            ]);

            return [
                'success' => true,
                'message' => "تم تسجيل القسم ({$name}) بنجاح في الهيكل التنظيمي.",
                'record_name' => $name,
                'details' => [
                    'name' => $name,
                    'code' => $code ?: '--',
                ]
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ القسم: ' . $e->getMessage(),
            ];
        }
    }
}
