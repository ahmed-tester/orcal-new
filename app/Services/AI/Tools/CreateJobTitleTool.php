<?php

namespace App\Services\AI\Tools;

use App\Enums\GeneralStatus;
use App\Models\Department;
use App\Models\JobTitle;
use App\Services\AI\Contracts\GeminiToolInterface;
use App\Services\CompanyAccessScopeService;
use App\Services\CompanyFormService;

class CreateJobTitleTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_job_title_record';
    }

    public function getDeclaration(): array
    {
        $key = 'organization.job_titles.ai.';

        return [
            'name' => $this->getName(),
            'description' => __($key.'description'),
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => [
                        'type' => 'STRING',
                        'description' => __($key.'name'),
                    ],
                    'department_name' => [
                        'type' => 'STRING',
                        'description' => __($key.'department_name'),
                    ],
                    'job_description' => [
                        'type' => 'STRING',
                        'description' => __($key.'job_description'),
                    ],
                ],
                'required' => ['name'],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $name = trim($arguments['name'] ?? '');
            $deptName = trim($arguments['department_name'] ?? '');
            $description = trim($arguments['job_description'] ?? '');

            if (empty($name)) {
                return ['success' => false, 'message' => __('organization.job_titles.ai.name_required')];
            }

            $companyId = auth()->user()?->company_id ?? CompanyFormService::getDefaultCompanyId();
            if (! $companyId) {
                return ['success' => false, 'message' => __('organization.departments.ai.company_required')];
            }

            $departmentId = null;
            if (! empty($deptName)) {
                $dept = CompanyAccessScopeService::apply(Department::query())
                    ->where('name', $deptName)
                    ->where(fn ($query) => $query->where('company_id', $companyId)
                        ->orWhereHas('companies', fn ($companies) => $companies->where('companies.id', $companyId)))
                    ->first();
                if (! $dept) {
                    return ['success' => false, 'message' => __('organization.departments.ai.select_existing')];
                }
                $departmentId = $dept->id;
            }

            $existing = JobTitle::where('company_id', $companyId)
                ->where('name', $name)
                ->first();

            if ($existing) {
                return [
                    'success' => false,
                    'message' => __('organization.job_titles.ai.duplicate', ['name' => $name]),
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
                'message' => __('organization.job_titles.ai.created', ['name' => $name]),
                'record_name' => $name,
                'details' => [
                    'name' => $name,
                    'department' => $deptName ?: '--',
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => __('organization.job_titles.ai.failed', ['error' => $e->getMessage()]),
            ];
        }
    }
}
