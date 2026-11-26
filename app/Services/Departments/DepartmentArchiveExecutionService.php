<?php

namespace App\Services\Departments;

use App\Models\Department;
use App\Models\DepartmentArchiveBatch;
use App\Models\DepartmentArchiveItem;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Services\CompanyAccessScopeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DepartmentArchiveExecutionService
{
    /**
     * Transfer all direct links from source department(s) to target, then soft-delete the source(s).
     *
     * @param  Department|Collection<int, Department>  $sources
     */
    public static function transferAndArchive(Department|Collection $sources, int $targetDepartmentId): DepartmentArchiveBatch
    {
        return DepartmentHierarchyService::transaction(function () use ($sources, $targetDepartmentId): DepartmentArchiveBatch {
            $sourceCollection = $sources instanceof Department ? collect([$sources]) : $sources;
            $sourceIds = $sourceCollection->pluck('id')->map(fn ($id) => (int) $id)->unique()->values()->all();

            if ($sourceIds === []) {
                throw ValidationException::withMessages([
                    'departments' => __('organization.departments.deletion.errors.no_departments_selected'),
                ]);
            }

            $lockedSources = Department::query()
                ->with('companies')
                ->whereKey($sourceIds)
                ->lockForUpdate()
                ->get();

            if ($lockedSources->count() !== count($sourceIds)) {
                throw ValidationException::withMessages([
                    'departments' => __('organization.departments.deletion.errors.department_missing'),
                ]);
            }

            $target = CompanyAccessScopeService::apply(Department::query())
                ->with('companies')
                ->lockForUpdate()
                ->find($targetDepartmentId);

            if (! $target || ! DepartmentImpactService::canTransferAllToTarget($lockedSources, $target)) {
                throw ValidationException::withMessages([
                    'target_department_id' => __('organization.departments.deletion.errors.incompatible_target'),
                ]);
            }

            $directChildren = Department::query()
                ->with('companies')
                ->whereIn('parent_id', $sourceIds)
                ->whereNotIn('id', $sourceIds)
                ->lockForUpdate()
                ->ordered()
                ->get();

            $directEmployees = Employee::query()
                ->whereIn('department_id', $sourceIds)
                ->lockForUpdate()
                ->get();

            $directJobTitles = Schema::hasTable('job_titles')
                ? JobTitle::query()->whereIn('department_id', $sourceIds)->lockForUpdate()->get()
                : collect();

            DepartmentDeletionSupport::authorizeDeletionScope($lockedSources, $directEmployees, $directJobTitles);
            DepartmentDeletionSupport::authorizeTargetScope($target);

            $childPreviousParents = $directChildren->mapWithKeys(fn (Department $child) => [$child->id => $child->parent_id])->all();
            $employeePreviousDepts = $directEmployees->mapWithKeys(fn (Employee $emp) => [$emp->id => $emp->department_id])->all();
            $jobTitlePreviousDepts = $directJobTitles->mapWithKeys(fn (JobTitle $jt) => [$jt->id => $jt->department_id])->all();

            foreach ($directChildren as $child) {
                DepartmentHierarchyService::move($child, (int) $target->id);
            }

            if ($directEmployees->isNotEmpty()) {
                Employee::query()
                    ->whereKey($directEmployees->pluck('id')->all())
                    ->update(['department_id' => $target->id]);
            }

            if ($directJobTitles->isNotEmpty()) {
                JobTitle::query()
                    ->whereKey($directJobTitles->pluck('id')->all())
                    ->update(['department_id' => $target->id]);
            }

            $primaryRoot = $lockedSources->sortBy(fn (Department $d) => [DepartmentHierarchyService::levelOf($d), $d->id])->first();

            $batch = DepartmentArchiveBatch::create([
                'action_type' => 'transfer_and_archive',
                'actor_user_id' => auth()->id(),
                'root_department_id' => $primaryRoot->id,
                'root_department_name' => $lockedSources->count() > 1
                    ? $primaryRoot->name.' (+'.($lockedSources->count() - 1).')'
                    : $primaryRoot->name,
                'root_department_code' => $primaryRoot->department_code,
                'target_department_id' => $target->id,
                'target_department_name' => $target->name,
                'department_ids' => $sourceIds,
                'summary' => [
                    'archived_departments' => $lockedSources->count(),
                    'transferred_children' => $directChildren->count(),
                    'transferred_employees' => $directEmployees->count(),
                    'transferred_job_titles' => $directJobTitles->count(),
                ],
            ]);

            foreach ($directChildren as $child) {
                DepartmentArchiveItem::create([
                    'batch_id' => $batch->id,
                    'item_type' => 'transferred_department',
                    'item_id' => $child->id,
                    'parent_department_id' => $childPreviousParents[$child->id] ?? null,
                    'company_id' => $child->company_id,
                    'previous_state' => [
                        'name' => $child->name,
                        'department_code' => $child->department_code,
                        'previous_parent_id' => $childPreviousParents[$child->id] ?? null,
                        'new_parent_id' => $target->id,
                    ],
                ]);
            }

            foreach ($directEmployees as $emp) {
                DepartmentArchiveItem::create([
                    'batch_id' => $batch->id,
                    'item_type' => 'transferred_employee',
                    'item_id' => $emp->id,
                    'parent_department_id' => $employeePreviousDepts[$emp->id] ?? null,
                    'company_id' => $emp->company_id,
                    'previous_state' => [
                        'name' => $emp->name,
                        'previous_department_id' => $employeePreviousDepts[$emp->id] ?? null,
                        'new_department_id' => $target->id,
                    ],
                ]);
            }

            foreach ($directJobTitles as $jt) {
                DepartmentArchiveItem::create([
                    'batch_id' => $batch->id,
                    'item_type' => 'transferred_job_title',
                    'item_id' => $jt->id,
                    'parent_department_id' => $jobTitlePreviousDepts[$jt->id] ?? null,
                    'company_id' => $jt->company_id,
                    'previous_state' => [
                        'name' => $jt->name,
                        'previous_department_id' => $jobTitlePreviousDepts[$jt->id] ?? null,
                        'new_department_id' => $target->id,
                    ],
                ]);
            }

            $orderedForDelete = $lockedSources
                ->sortByDesc(fn (Department $d) => [DepartmentHierarchyService::levelOf($d), $d->id])
                ->values();

            foreach ($orderedForDelete as $source) {
                DepartmentArchiveItem::create([
                    'batch_id' => $batch->id,
                    'item_type' => 'department',
                    'item_id' => $source->id,
                    'parent_department_id' => $source->parent_id,
                    'company_id' => $source->company_id,
                    'previous_state' => [
                        'name' => $source->name,
                        'department_code' => $source->department_code,
                        'parent_id' => $source->parent_id,
                        'manager_id' => $source->manager_id,
                        'sort_order' => $source->sort_order,
                        'is_global' => (bool) $source->is_global,
                        'company_id' => $source->company_id,
                        'status' => $source->status?->value ?? $source->status,
                    ],
                ]);

                if (! $source->delete()) {
                    throw ValidationException::withMessages([
                        'departments' => __('organization.departments.deletion.errors.delete_failed'),
                    ]);
                }
            }

            return $batch;
        });
    }

    /**
     * Soft-delete department(s) and all linked subtree departments, employees, and job titles in one transaction.
     *
     * @param  Department|Collection<int, Department>  $sources
     */
    public static function archiveWithDependents(
        Department|Collection $sources,
        string $confirmationText = '',
        bool $requireConfirmation = true
    ): DepartmentArchiveBatch {
        return DepartmentHierarchyService::transaction(function () use ($sources, $confirmationText, $requireConfirmation): DepartmentArchiveBatch {
            $sourceCollection = $sources instanceof Department ? collect([$sources]) : $sources;
            $selectedIds = $sourceCollection->pluck('id')->map(fn ($id) => (int) $id)->unique()->values()->all();

            if ($selectedIds === []) {
                throw ValidationException::withMessages([
                    'departments' => __('organization.departments.deletion.errors.no_departments_selected'),
                ]);
            }

            $rootIds = DepartmentDeletionSupport::deduplicateRootIds($selectedIds);
            $allDepartmentIds = DepartmentDeletionSupport::collectSubtreeIds($rootIds);

            $lockedDepartments = Department::query()
                ->with('companies')
                ->whereKey($allDepartmentIds)
                ->lockForUpdate()
                ->get();

            if ($lockedDepartments->count() !== count($allDepartmentIds)) {
                throw ValidationException::withMessages([
                    'departments' => __('organization.departments.deletion.errors.department_missing'),
                ]);
            }

            $employees = Employee::query()
                ->with('user')
                ->whereIn('department_id', $allDepartmentIds)
                ->lockForUpdate()
                ->get();

            $jobTitles = Schema::hasTable('job_titles')
                ? JobTitle::query()->whereIn('department_id', $allDepartmentIds)->lockForUpdate()->get()
                : collect();

            DepartmentDeletionSupport::authorizeDeletionScope($lockedDepartments, $employees, $jobTitles);

            $roots = $lockedDepartments->whereIn('id', $rootIds)->values();
            $primaryRoot = $roots->sortBy(fn (Department $d) => [DepartmentHierarchyService::levelOf($d), $d->id])->first();

            if (! $requireConfirmation) {
                $hasDependents = $lockedDepartments->count() > $roots->count()
                    || $employees->isNotEmpty()
                    || $jobTitles->isNotEmpty();

                if ($hasDependents) {
                    throw ValidationException::withMessages([
                        'confirmation_text' => __('organization.departments.deletion.errors.impact_changed_requires_confirmation'),
                    ]);
                }
            }

            if ($requireConfirmation) {
                $trimmed = trim($confirmationText);
                $validConfirmation = false;

                if ($roots->count() === 1) {
                    $validConfirmation = $trimmed !== ''
                        && ($trimmed === trim((string) $primaryRoot->name)
                            || (filled($primaryRoot->department_code) && mb_strtoupper($trimmed) === mb_strtoupper(trim((string) $primaryRoot->department_code))));
                } else {
                    $validConfirmation = in_array(mb_strtoupper($trimmed), ['حذف', 'DELETE', mb_strtoupper(trim((string) $primaryRoot->name)), mb_strtoupper(trim((string) $primaryRoot->department_code))], true);
                }

                if (! $validConfirmation) {
                    throw ValidationException::withMessages([
                        'confirmation_text' => __('organization.departments.deletion.errors.confirmation_mismatch'),
                    ]);
                }
            }

            if (DepartmentDeletionSupport::hasExternalJobTitleLinks($jobTitles, $allDepartmentIds)) {
                throw ValidationException::withMessages([
                    'departments' => __('organization.departments.deletion.errors.external_job_title_links'),
                ]);
            }

            $batch = DepartmentArchiveBatch::create([
                'action_type' => 'archive',
                'actor_user_id' => auth()->id(),
                'root_department_id' => $primaryRoot->id,
                'root_department_name' => $roots->count() > 1
                    ? $primaryRoot->name.' (+'.($roots->count() - 1).')'
                    : $primaryRoot->name,
                'root_department_code' => $primaryRoot->department_code,
                'department_ids' => $allDepartmentIds,
                'summary' => [
                    'archived_departments' => $lockedDepartments->count(),
                    'archived_employees' => $employees->count(),
                    'archived_job_titles' => $jobTitles->count(),
                ],
            ]);

            foreach ($jobTitles as $jobTitle) {
                DepartmentArchiveItem::create([
                    'batch_id' => $batch->id,
                    'item_type' => 'job_title',
                    'item_id' => $jobTitle->id,
                    'parent_department_id' => $jobTitle->department_id,
                    'company_id' => $jobTitle->company_id,
                    'previous_state' => [
                        'name' => $jobTitle->name,
                        'department_id' => $jobTitle->department_id,
                        'company_id' => $jobTitle->company_id,
                        'status' => $jobTitle->status?->value ?? $jobTitle->status,
                    ],
                ]);

                $jobTitle->delete();
            }

            foreach ($employees as $employee) {
                DepartmentArchiveItem::create([
                    'batch_id' => $batch->id,
                    'item_type' => 'employee',
                    'item_id' => $employee->id,
                    'parent_department_id' => $employee->department_id,
                    'company_id' => $employee->company_id,
                    'previous_state' => [
                        'name' => $employee->name,
                        'department_id' => $employee->department_id,
                        'company_id' => $employee->company_id,
                        'user_id' => $employee->user_id,
                        'status' => $employee->status?->value ?? $employee->status,
                    ],
                ]);

                $employee->delete();
            }

            $orderedDepartments = $lockedDepartments
                ->sortByDesc(fn (Department $d) => [DepartmentHierarchyService::levelOf($d), $d->id])
                ->values();

            foreach ($orderedDepartments as $dept) {
                DepartmentArchiveItem::create([
                    'batch_id' => $batch->id,
                    'item_type' => 'department',
                    'item_id' => $dept->id,
                    'parent_department_id' => $dept->parent_id,
                    'company_id' => $dept->company_id,
                    'previous_state' => [
                        'name' => $dept->name,
                        'department_code' => $dept->department_code,
                        'parent_id' => $dept->parent_id,
                        'manager_id' => $dept->manager_id,
                        'sort_order' => $dept->sort_order,
                        'is_global' => (bool) $dept->is_global,
                        'company_id' => $dept->company_id,
                        'status' => $dept->status?->value ?? $dept->status,
                    ],
                ]);

                if (! $dept->delete()) {
                    throw ValidationException::withMessages([
                        'departments' => __('organization.departments.deletion.errors.delete_failed'),
                    ]);
                }
            }

            return $batch;
        });
    }
}
