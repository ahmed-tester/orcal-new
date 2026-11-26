<?php

namespace App\Services\Departments;

use App\Models\Department;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Owner;
use App\Services\CompanyAccessScopeService;
use App\Services\CompanyFormService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DepartmentImpactService
{
    /**
     * Analyze the full impact of deleting or transferring a single department.
     *
     * @return array<string, mixed>
     */
    public static function analyzeImpact(Department $department): array
    {
        return static::analyzeBulkImpact(collect([$department]));
    }

    /**
     * Analyze the full impact of deleting or transferring one or more departments.
     *
     * @param  Collection<int, Department>  $departments
     * @return array<string, mixed>
     */
    public static function analyzeBulkImpact(Collection $departments): array
    {
        if ($departments->isEmpty()) {
            throw ValidationException::withMessages([
                'departments' => __('organization.departments.deletion.errors.no_departments_selected'),
            ]);
        }

        $selectedIds = $departments->pluck('id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $rootIds = DepartmentDeletionSupport::deduplicateRootIds($selectedIds);
        $allDepartmentIds = DepartmentDeletionSupport::collectSubtreeIds($rootIds);

        $allDepartments = Department::query()
            ->with(['companies', 'company'])
            ->whereKey($allDepartmentIds)
            ->get()
            ->sortBy(fn (Department $dept) => [DepartmentHierarchyService::levelOf($dept), $dept->sort_order, $dept->id])
            ->values();

        $employees = Employee::query()
            ->with(['user', 'company'])
            ->whereIn('department_id', $allDepartmentIds)
            ->get();

        $jobTitles = Schema::hasTable('job_titles')
            ? JobTitle::query()->whereIn('department_id', $allDepartmentIds)->get()
            : collect();

        DepartmentDeletionSupport::authorizeDeletionScope($allDepartments, $employees, $jobTitles);

        $directChildrenCount = Department::query()
            ->whereIn('parent_id', $rootIds)
            ->whereNotIn('id', $rootIds)
            ->count();

        $totalDescendantsCount = max(0, count($allDepartmentIds) - count($rootIds));

        $directEmployeesCount = Employee::query()
            ->whereIn('department_id', $rootIds)
            ->count();

        $directJobTitlesCount = Schema::hasTable('job_titles')
            ? JobTitle::query()->whereIn('department_id', $rootIds)->count()
            : 0;

        $blockers = [];
        if ($directChildrenCount > 0 || $totalDescendantsCount > 0) {
            $blockers[] = 'has_children';
        }
        if ($employees->isNotEmpty()) {
            $blockers[] = 'has_employees';
        }
        if ($jobTitles->isNotEmpty()) {
            $blockers[] = 'has_job_titles';
        }

        $ownerUserIds = Schema::hasTable('owners')
            ? Owner::query()->whereIn('user_id', $employees->pluck('user_id')->filter()->all())->pluck('user_id')->all()
            : [];

        $financialCount = DepartmentDeletionSupport::countPreservedFinancialRecords($employees->pluck('id')->all());
        $warnings = DepartmentDeletionSupport::collectWarnings($allDepartments, $employees, $jobTitles, $allDepartmentIds);
        $hasExternalJobTitleLinks = DepartmentDeletionSupport::hasExternalJobTitleLinks($jobTitles, $allDepartmentIds);
        $compatibleTargets = static::compatibleTransferTargets($departments);

        $roots = $allDepartments->whereIn('id', $rootIds)->values();

        return [
            'is_bulk' => count($rootIds) > 1,
            'root_ids' => $rootIds,
            'all_department_ids' => $allDepartmentIds,
            'roots' => $roots->map(fn (Department $d) => [
                'id' => (int) $d->id,
                'name' => $d->name,
                'code' => $d->department_code,
                'level' => DepartmentHierarchyService::levelOf($d),
                'level_label' => DepartmentHierarchyService::levelLabel($d),
            ])->all(),
            'has_blockers' => $blockers !== [],
            'blockers' => $blockers,
            'has_external_job_title_links' => $hasExternalJobTitleLinks,
            'counts' => [
                'selected_roots' => count($rootIds),
                'direct_children' => $directChildrenCount,
                'total_descendants' => $totalDescendantsCount,
                'total_departments' => count($allDepartmentIds),
                'direct_employees' => $directEmployeesCount,
                'total_employees' => $employees->count(),
                'direct_job_titles' => $directJobTitlesCount,
                'total_job_titles' => $jobTitles->count(),
                'financial_records' => $financialCount,
            ],
            'affected_departments' => $allDepartments->map(fn (Department $d) => [
                'id' => (int) $d->id,
                'name' => $d->name,
                'code' => $d->department_code,
                'level' => DepartmentHierarchyService::levelOf($d),
                'level_label' => DepartmentHierarchyService::levelLabel($d),
            ])->all(),
            'affected_employees' => $employees->map(fn (Employee $e) => [
                'id' => (int) $e->id,
                'name' => $e->name,
                'company_id' => $e->company_id ? (int) $e->company_id : null,
                'company_name' => $e->company?->trade_name,
                'has_other_capacity' => $e->user_id && in_array($e->user_id, $ownerUserIds, true),
            ])->all(),
            'affected_job_titles' => $jobTitles->map(fn (JobTitle $jt) => [
                'id' => (int) $jt->id,
                'name' => $jt->name,
                'company_id' => $jt->company_id ? (int) $jt->company_id : null,
            ])->all(),
            'warnings' => $warnings,
            'compatible_targets' => $compatibleTargets,
        ];
    }

    /**
     * Find all active, authorized target departments that can receive ALL links from the source department(s).
     *
     * @param  Department|Collection<int, Department>  $sources
     * @return array<int, string>
     */
    public static function compatibleTransferTargets(Department|Collection $sources): array
    {
        $sourceCollection = $sources instanceof Department ? collect([$sources]) : $sources;
        if ($sourceCollection->isEmpty()) {
            return [];
        }

        $rootIds = DepartmentDeletionSupport::deduplicateRootIds(
            $sourceCollection->pluck('id')->map(fn ($id) => (int) $id)->all()
        );
        $excludedIds = DepartmentDeletionSupport::collectSubtreeIds($rootIds);

        $candidates = CompanyAccessScopeService::apply(Department::query())
            ->with('companies')
            ->where('status', 'active')
            ->whereNotIn('id', $excludedIds)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $compatible = $candidates->filter(
            fn (Department $candidate): bool => static::canTransferAllToTarget($sourceCollection, $candidate)
        );

        return DepartmentHierarchyService::pathLabels($compatible);
    }

    /**
     * Check whether a candidate target department is valid for transferring all direct links from sources.
     *
     * @param  Collection<int, Department>  $sources
     */
    public static function canTransferAllToTarget(Collection $sources, Department $target): bool
    {
        if ($target->trashed() || ($target->status?->value ?? $target->status) !== 'active') {
            return false;
        }

        $sourceIds = $sources->pluck('id')->map(fn ($id) => (int) $id)->all();
        $excludedIds = DepartmentDeletionSupport::collectSubtreeIds($sourceIds);
        if (in_array((int) $target->id, $excludedIds, true)) {
            return false;
        }

        $targetLevel = DepartmentHierarchyService::levelOf($target);
        $targetCompanyIds = DepartmentHierarchyService::companyIds($target);
        $targetIsOrganizational = $target->is_global && $targetCompanyIds === [];

        $user = auth()->user();
        if ($user && ! $user->isSuperAdmin()) {
            $authorizedCompanyIds = array_map('intval', CompanyFormService::getAuthorizedCompanyIds($user));
            if ($authorizedCompanyIds === [] || $targetCompanyIds === [] || array_diff($targetCompanyIds, $authorizedCompanyIds) !== []) {
                return false;
            }
        }

        // 1. Check direct children of sources (excluding children that are also in $sourceIds)
        $directChildren = Department::query()
            ->with('companies')
            ->whereIn('parent_id', $sourceIds)
            ->whereNotIn('id', $sourceIds)
            ->get();

        if ($directChildren->isNotEmpty()) {
            $newChildLevel = $targetLevel + 1;
            if ($newChildLevel > DepartmentHierarchyService::MAX_DEPTH) {
                return false;
            }

            try {
                DepartmentHierarchyService::authorizeUpdates($directChildren);
            } catch (AuthorizationException) {
                return false;
            }

            foreach ($directChildren as $child) {
                if (($newChildLevel + DepartmentHierarchyService::subtreeDepth((int) $child->id) - 1) > DepartmentHierarchyService::MAX_DEPTH) {
                    return false;
                }

                $childCompanyIds = DepartmentHierarchyService::companyIds($child);
                $childIsGroup = $child->is_global && $childCompanyIds === [];

                if ($newChildLevel <= 2 && ! $childIsGroup) {
                    return false;
                }

                if ($newChildLevel === DepartmentHierarchyService::MAX_DEPTH && ($childIsGroup || $childCompanyIds === [])) {
                    return false;
                }

                if (! $targetIsOrganizational && $targetCompanyIds !== [] && ($childCompanyIds === [] || array_diff($childCompanyIds, $targetCompanyIds) !== [])) {
                    return false;
                }

                if (filled($child->department_code)) {
                    $codeExists = Department::withoutGlobalScopes()
                        ->where('parent_id', $target->id)
                        ->where('department_code', $child->department_code)
                        ->whereKeyNot($child->id)
                        ->exists();

                    if ($codeExists) {
                        return false;
                    }
                }
            }
        }

        // 2. Check direct employees of sources
        if (! $targetIsOrganizational) {
            if ($targetCompanyIds === []) {
                return false;
            }

            $incompatibleEmployeeExists = Employee::query()
                ->whereIn('department_id', $sourceIds)
                ->whereNotNull('company_id')
                ->whereNotIn('company_id', $targetCompanyIds)
                ->exists();

            if ($incompatibleEmployeeExists) {
                return false;
            }
        }

        // 3. Check direct job titles of sources
        if (Schema::hasTable('job_titles') && ! $targetIsOrganizational) {
            $incompatibleJobTitleExists = JobTitle::query()
                ->whereIn('department_id', $sourceIds)
                ->where('is_global', false)
                ->whereNotNull('company_id')
                ->whereNotIn('company_id', $targetCompanyIds)
                ->exists();

            if ($incompatibleJobTitleExists) {
                return false;
            }
        }

        return true;
    }
}
