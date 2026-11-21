<?php

namespace App\Services\AI\Tools;

use App\Enums\DepartmentScope;
use App\Models\Department;
use App\Services\AI\Contracts\GeminiToolInterface;
use App\Services\CompanyAccessScopeService;
use App\Services\CompanyFormService;
use App\Services\DepartmentHierarchyService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class CreateDepartmentTool implements GeminiToolInterface
{
    public function getName(): string
    {
        return 'create_department_record';
    }

    public function getDeclaration(): array
    {
        $key = 'organization.departments.ai.';

        return [
            'name' => $this->getName(),
            'description' => __($key.'description'),
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'name' => ['type' => 'STRING', 'description' => __($key.'name')],
                    'parent_id' => ['type' => 'INTEGER', 'description' => __($key.'parent')],
                    'company_id' => ['type' => 'INTEGER', 'description' => __($key.'company')],
                    'companies' => ['type' => 'ARRAY', 'items' => ['type' => 'INTEGER'], 'description' => __($key.'companies')],
                    'description' => ['type' => 'STRING', 'description' => __($key.'details')],
                ],
                'required' => ['name'],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        try {
            $name = trim($arguments['name'] ?? '');
            $parentId = ! empty($arguments['parent_id']) ? (int) $arguments['parent_id'] : null;
            if ($name === '') {
                return ['success' => false, 'message' => __('organization.departments.ai.name_required')];
            }

            $companies = array_values(array_unique(array_filter(array_map('intval', $arguments['companies'] ?? []))));
            $targetLevel = DepartmentHierarchyService::targetLevelForParent($parentId);

            if ($targetLevel > DepartmentHierarchyService::MAX_DEPTH) {
                return ['success' => false, 'message' => __('organization.departments.hierarchy.errors.max_depth_exceeded')];
            }

            if ($targetLevel <= 2) {
                if (! empty($arguments['company_id']) || $companies !== []) {
                    return ['success' => false, 'message' => __('organization.departments.hierarchy.errors.general_level_forbids_companies')];
                }
                $scope = DepartmentScope::FAWAZ_GROUP;
                $companyId = null;
                $companies = [];
            } else {
                if (count($companies) >= 2) {
                    $scope = DepartmentScope::SHARED;
                    $companyId = null;
                } elseif (count($companies) === 1) {
                    $scope = DepartmentScope::SINGLE;
                    $companyId = $companies[0];
                    $companies = [];
                } else {
                    $scope = DepartmentScope::SINGLE;
                    $companyId = isset($arguments['company_id']) ? (int) $arguments['company_id'] : CompanyFormService::getDefaultCompanyId();
                }

                if ($scope === DepartmentScope::SINGLE && ! $companyId) {
                    return ['success' => false, 'message' => __('organization.departments.ai.company_required')];
                }
            }

            if (CompanyAccessScopeService::apply(Department::query())->where('parent_id', $parentId)->where('name', $name)->exists()) {
                return ['success' => false, 'message' => __('organization.departments.ai.duplicate', ['name' => $name])];
            }

            $department = DepartmentHierarchyService::save([
                'name' => $name,
                'parent_id' => $parentId,
                'company_id' => $companyId,
                'description' => $arguments['description'] ?? null,
                'status' => 'active',
            ], null, $scope, $companies);

            return [
                'success' => true,
                'message' => __('organization.departments.ai.created', ['name' => $name]),
                'record_name' => $name,
                'details' => ['name' => $name, 'code' => $department->department_code],
            ];
        } catch (ValidationException $exception) {
            return ['success' => false, 'message' => collect($exception->errors())->flatten()->implode(' ')];
        } catch (AuthorizationException $exception) {
            return ['success' => false, 'message' => __('organization.departments.ai.forbidden')];
        } catch (\Throwable $exception) {
            report($exception);

            return ['success' => false, 'message' => __('organization.departments.ai.failed')];
        }
    }
}
