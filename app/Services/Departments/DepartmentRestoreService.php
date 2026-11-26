<?php

namespace App\Services\Departments;

use App\Models\Department;
use App\Models\DepartmentArchiveBatch;
use App\Models\DepartmentArchiveItem;
use App\Models\Employee;
use App\Models\JobTitle;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DepartmentRestoreService
{
    /**
     * Administratively restore all records archived in a single batch (Super Admin only).
     */
    public static function restoreBatch(DepartmentArchiveBatch $batch, ?int $overrideParentId = null): void
    {
        if (! auth()->user()?->isSuperAdmin()) {
            throw new AuthorizationException;
        }

        DepartmentHierarchyService::transaction(function () use ($batch, $overrideParentId): void {
            $lockedBatch = DepartmentArchiveBatch::query()->lockForUpdate()->findOrFail($batch->id);

            if ($lockedBatch->restored_at !== null) {
                throw ValidationException::withMessages([
                    'batch' => __('organization.departments.deletion.errors.already_restored'),
                ]);
            }

            $items = DepartmentArchiveItem::query()
                ->where('batch_id', $lockedBatch->id)
                ->whereNull('restored_at')
                ->orderBy('id')
                ->get();

            $departmentItems = $items->where('item_type', 'department')->values();
            $batchDepartmentIds = $departmentItems->pluck('item_id')->map(fn ($id) => (int) $id)->all();

            $trashedDepartments = Department::onlyTrashed()
                ->with('companies')
                ->whereKey($batchDepartmentIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $orderedDepartmentIds = DepartmentDeletionSupport::sortIdsTopDown($trashedDepartments, $batchDepartmentIds);

            $jobTitleIds = $items->where('item_type', 'job_title')->pluck('item_id')->all();
            $trashedJobTitles = ($jobTitleIds !== [] && Schema::hasTable('job_titles'))
                ? JobTitle::onlyTrashed()->whereKey($jobTitleIds)->lockForUpdate()->get()
                : collect();

            $employeeIds = $items->where('item_type', 'employee')->pluck('item_id')->all();
            $trashedEmployees = $employeeIds !== []
                ? Employee::onlyTrashed()->with('user')->whereKey($employeeIds)->lockForUpdate()->get()
                : collect();

            static::validateRestoreState(
                $trashedDepartments,
                $orderedDepartmentIds,
                $batchDepartmentIds,
                $overrideParentId,
                $trashedEmployees,
                $trashedJobTitles
            );

            foreach ($orderedDepartmentIds as $deptId) {
                $dept = $trashedDepartments->get($deptId);
                if (! $dept) {
                    continue;
                }

                $isBatchRoot = ! in_array((int) $dept->parent_id, $batchDepartmentIds, true);
                if ($isBatchRoot && $overrideParentId !== null) {
                    $dept->parent_id = $overrideParentId;
                }

                $dept->restore();
            }

            if ($jobTitleIds !== [] && Schema::hasTable('job_titles')) {
                JobTitle::onlyTrashed()->whereKey($jobTitleIds)->restore();
            }

            if ($employeeIds !== []) {
                Employee::onlyTrashed()->whereKey($employeeIds)->restore();
            }

            $now = now();
            DepartmentArchiveItem::query()
                ->where('batch_id', $lockedBatch->id)
                ->whereIn('item_type', ['department', 'job_title', 'employee', 'user_access'])
                ->whereNull('restored_at')
                ->update(['restored_at' => $now]);

            $lockedBatch->update([
                'restored_at' => $now,
                'restored_by_user_id' => auth()->id(),
            ]);
        });
    }

    /**
     * Validate the full target hierarchy, code uniqueness, and company compatibility before restoring any record.
     *
     * @param  Collection<int, Department>  $trashedDepartments
     * @param  array<int, int>  $orderedDepartmentIds
     * @param  array<int, int>  $batchDepartmentIds
     * @param  Collection<int, Employee>  $trashedEmployees
     * @param  Collection<int, JobTitle>  $trashedJobTitles
     */
    protected static function validateRestoreState(
        Collection $trashedDepartments,
        array $orderedDepartmentIds,
        array $batchDepartmentIds,
        ?int $overrideParentId,
        Collection $trashedEmployees,
        Collection $trashedJobTitles
    ): void {
        $batchSet = array_flip($batchDepartmentIds);
        $resolvedLevels = [];
        $resolvedCompanies = [];
        $resolvedIsGlobal = [];

        foreach ($orderedDepartmentIds as $deptId) {
            $dept = $trashedDepartments->get($deptId);
            if (! $dept) {
                continue;
            }

            $isBatchRoot = ! isset($batchSet[(int) $dept->parent_id]);
            $effectiveParentId = ($isBatchRoot && $overrideParentId !== null)
                ? $overrideParentId
                : ($dept->parent_id ? (int) $dept->parent_id : null);

            if ($effectiveParentId === $deptId) {
                throw ValidationException::withMessages([
                    'override_parent_id' => __('organization.departments.deletion.errors.restore_hierarchy_incompatible'),
                ]);
            }

            $parentLevel = 0;
            $parentCompanyIds = [];
            $parentIsGlobal = true;

            if ($effectiveParentId !== null) {
                if (isset($resolvedLevels[$effectiveParentId])) {
                    $parentLevel = $resolvedLevels[$effectiveParentId];
                    $parentCompanyIds = $resolvedCompanies[$effectiveParentId];
                    $parentIsGlobal = $resolvedIsGlobal[$effectiveParentId];
                } else {
                    $activeParent = Department::query()->with('companies')->find($effectiveParentId);
                    if (! $activeParent) {
                        throw ValidationException::withMessages([
                            'override_parent_id' => $isBatchRoot && $overrideParentId !== null
                                ? __('organization.departments.hierarchy.errors.parent_missing')
                                : __('organization.departments.deletion.errors.parent_archived'),
                        ]);
                    }

                    $parentLevel = DepartmentHierarchyService::levelOf($activeParent);
                    $parentCompanyIds = DepartmentHierarchyService::companyIds($activeParent);
                    $parentIsGlobal = (bool) $activeParent->is_global && $parentCompanyIds === [];
                }
            }

            $effectiveLevel = $parentLevel + 1;
            if ($effectiveLevel > DepartmentHierarchyService::MAX_DEPTH) {
                throw ValidationException::withMessages([
                    'override_parent_id' => __('organization.departments.hierarchy.errors.max_depth_exceeded'),
                ]);
            }

            $deptCompanyIds = DepartmentHierarchyService::companyIds($dept);
            $deptIsGlobal = (bool) $dept->is_global && $deptCompanyIds === [];

            if ($effectiveLevel <= 2 && ! $deptIsGlobal) {
                throw ValidationException::withMessages([
                    'override_parent_id' => __('organization.departments.deletion.errors.restore_hierarchy_incompatible'),
                ]);
            }

            if ($effectiveLevel === DepartmentHierarchyService::MAX_DEPTH && ($deptIsGlobal || $deptCompanyIds === [])) {
                throw ValidationException::withMessages([
                    'override_parent_id' => __('organization.departments.deletion.errors.restore_hierarchy_incompatible'),
                ]);
            }

            if (! $parentIsGlobal && $parentCompanyIds !== [] && array_diff($deptCompanyIds, $parentCompanyIds) !== []) {
                throw ValidationException::withMessages([
                    'override_parent_id' => __('organization.departments.deletion.errors.restore_hierarchy_incompatible'),
                ]);
            }

            $originalParentId = $dept->parent_id;
            $dept->parent_id = $effectiveParentId;
            DepartmentHierarchyService::assertCodeAvailable($dept, 'override_');
            $dept->parent_id = $originalParentId;

            $resolvedLevels[$deptId] = $effectiveLevel;
            $resolvedCompanies[$deptId] = $deptCompanyIds;
            $resolvedIsGlobal[$deptId] = $deptIsGlobal;
        }

        foreach ($trashedEmployees as $employee) {
            $deptId = (int) $employee->department_id;
            if (isset($resolvedCompanies[$deptId])) {
                $deptCompanyIds = $resolvedCompanies[$deptId];
                $deptIsGlobal = $resolvedIsGlobal[$deptId];
            } else {
                $dept = Department::query()->with('companies')->find($deptId);
                if (! $dept) {
                    throw ValidationException::withMessages([
                        'override_parent_id' => __('organization.departments.deletion.errors.restore_employee_company_incompatible', [
                            'employee' => $employee->name,
                        ]),
                    ]);
                }
                $deptCompanyIds = DepartmentHierarchyService::companyIds($dept);
                $deptIsGlobal = (bool) $dept->is_global && $deptCompanyIds === [];
            }

            if (! $deptIsGlobal && $employee->company_id !== null && ! in_array((int) $employee->company_id, $deptCompanyIds, true)) {
                throw ValidationException::withMessages([
                    'override_parent_id' => __('organization.departments.deletion.errors.restore_employee_company_incompatible', [
                        'employee' => $employee->name,
                    ]),
                ]);
            }
        }

        foreach ($trashedJobTitles as $jobTitle) {
            if ($jobTitle->is_global || $jobTitle->company_id === null || $jobTitle->department_id === null) {
                continue;
            }

            $deptId = (int) $jobTitle->department_id;
            if (isset($resolvedCompanies[$deptId])) {
                $deptCompanyIds = $resolvedCompanies[$deptId];
                $deptIsGlobal = $resolvedIsGlobal[$deptId];
            } else {
                $dept = Department::query()->with('companies')->find($deptId);
                if (! $dept) {
                    throw ValidationException::withMessages([
                        'override_parent_id' => __('organization.departments.deletion.errors.restore_job_title_company_incompatible', [
                            'title' => $jobTitle->name,
                        ]),
                    ]);
                }
                $deptCompanyIds = DepartmentHierarchyService::companyIds($dept);
                $deptIsGlobal = (bool) $dept->is_global && $deptCompanyIds === [];
            }

            if (! $deptIsGlobal && ! in_array((int) $jobTitle->company_id, $deptCompanyIds, true)) {
                throw ValidationException::withMessages([
                    'override_parent_id' => __('organization.departments.deletion.errors.restore_job_title_company_incompatible', [
                        'title' => $jobTitle->name,
                    ]),
                ]);
            }
        }
    }
}
