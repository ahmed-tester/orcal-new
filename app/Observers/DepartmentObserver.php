<?php

namespace App\Observers;

use App\Models\Department;
use App\Services\DepartmentHierarchyService;
use Illuminate\Validation\ValidationException;

class DepartmentObserver
{
    public function creating(Department $department): void
    {
        if (blank($department->department_code)) {
            $department->department_code = DepartmentHierarchyService::generateCode($department->parent_id);
        }

        if (blank($department->sort_order)) {
            $department->sort_order = DepartmentHierarchyService::nextSortOrder($department->parent_id);
        }

        if (blank($department->description)) {
            $department->description = null;
        }
    }

    public function saving(Department $department): void
    {
        if ($department->exists && filled($department->getOriginal('department_code'))) {
            $department->department_code = $department->getOriginal('department_code');
        }
        if (blank($department->description)) {
            $department->description = null;
        }

        if ($department->exists && $department->sort_order === null) {
            $department->sort_order = $department->getOriginal('sort_order') ?? 0;
        }

        if ($department->isDirty('parent_id')) {
            DepartmentHierarchyService::assertParentAssignable($department, $department->parent_id);

            if ($department->exists && ! $department->parent_id && (! $department->is_global
                || ($department->pendingCompanyIds ?? $department->companies()->pluck('companies.id')->all()) !== [])) {
                throw ValidationException::withMessages([
                    'parent_id' => __('organization.departments.hierarchy.errors.root_requires_group_scope'),
                ]);
            }

            if ($department->exists && ! $department->isDirty('sort_order')) {
                $department->sort_order = DepartmentHierarchyService::nextSortOrder($department->parent_id);
            }
        }

        if ($department->exists && ($department->isDirty(['company_id', 'is_global']) || $department->pendingCompanyIds !== null)) {
            DepartmentHierarchyService::assertDependentsCompatible(
                $department,
                $department->pendingCompanyIds ?? ($department->is_global
                    ? $department->companies()->pluck('companies.id')->all()
                    : array_filter([(int) $department->company_id])),
            );
        }

        if (($department->isDirty(['parent_id', 'company_id', 'is_global']) || ! $department->exists) && ($department->pendingCompanyIds !== null || ! $department->is_global || $department->exists)) {
            DepartmentHierarchyService::assertCompaniesWithinParent(
                $department->parent_id,
                (bool) $department->is_global,
                $department->company_id,
                $department->pendingCompanyIds ?? ($department->is_global ? $department->companies()->pluck('companies.id')->all() : []),
            );
        }
    }

    public function saved(Department $department): void
    {
        DepartmentHierarchyService::clearDisplayCache();
        if ($department->pendingCompanyIds !== null) {
            return;
        }
        if ($department->is_global) {
            $firstCompanyId = $department->companies()->value('companies.id');
            if ($firstCompanyId && $department->company_id !== $firstCompanyId) {
                $department->withoutEvents(function () use ($department, $firstCompanyId) {
                    $department->updateQuietly(['company_id' => $firstCompanyId]);
                });
            }
        } elseif ($department->company_id) {
            $department->companies()->syncWithoutDetaching([$department->company_id]);
        }
    }

    public function deleting(Department $department): bool
    {
        return DepartmentHierarchyService::deletionBlockers($department) === [];
    }

    public function deleted(Department $department): void
    {
        DepartmentHierarchyService::clearDisplayCache();
    }
}
