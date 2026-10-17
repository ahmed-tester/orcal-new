<?php

namespace App\Services\AI\Tools;

use App\Enums\GeneralStatus;
use App\Models\Company;
use App\Models\Department;
use App\Models\JobTitle;
use App\Services\AI\Contracts\GeminiToolInterface;

class CreateJobTitleTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_job_title_record';
    }

    public function getDeclaration(): array
    {
        return [
            'name' => $this->getName(),
            'description' => 'إدراج مسمى وظيفي جديد في المنشأة مع إمكانية ربطه بالقسم.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => [
                        'type' => 'STRING',
                        'description' => 'المسمى الوظيفي (مثال: محاسب قانوني، مهندس برمجيات، مدير مبيعات)'
                    ],
                    'department_name' => [
                        'type' => 'STRING',
                        'description' => 'اسم القسم التابع له (اختياري)'
                    ],
                    'job_description' => [
                        'type' => 'STRING',
                        'description' => 'الوصف الوظيفي والمسؤوليات (اختياري)'
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
            $deptName = trim($arguments['department_name'] ?? '');
            $description = trim($arguments['job_description'] ?? '');

            if (empty($name)) {
                return ['success' => false, 'message' => 'المسمى الوظيفي مطلوب.'];
            }

            $companyId = auth()->user()?->company_id ?? Company::first()?->id ?? 1;

            $departmentId = null;
            if (!empty($deptName)) {
                $dept = Department::firstOrCreate(
                    ['company_id' => $companyId, 'name' => $deptName],
                    ['status' => GeneralStatus::ACTIVE]
                );
                $departmentId = $dept->id;
            }

            $existing = JobTitle::where('company_id', $companyId)
                ->where('name', $name)
                ->first();

            if ($existing) {
                return [
                    'success' => false,
                    'message' => "تنبيه (مكرر): المسمى الوظيفي ({$name}) مسجل مسبقاً، تم تخطيه.",
                ];
            }

            $job = JobTitle::create([
                'company_id' => $companyId,
                'department_id' => $departmentId,
                'name' => $name,
                'job_description' => $description ?: null,
                'status' => GeneralStatus::ACTIVE,
            ]);

            return [
                'success' => true,
                'message' => "تم تسجيل المسمى الوظيفي ({$name}) بنجاح.",
                'record_name' => $name,
                'details' => [
                    'name' => $name,
                    'department' => $deptName ?: '--',
                ]
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ المسمى الوظيفي: ' . $e->getMessage(),
            ];
        }
    }
}
