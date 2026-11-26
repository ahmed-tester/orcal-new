<?php

namespace App\Services\Departments;

use App\Models\Department;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Services\CompanyAccessScopeService;
use App\Services\CompanyFormService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class DepartmentDeletionSupport
{
    /**
     * Authorize full deletion/transfer scope for every source department and affected record.
     *
     * @param  Collection<int, Department>  $departments
     * @param  Collection<int, Employee>|null  $employees
     * @param  Collection<int, JobTitle>|null  $jobTitles
     */
    public static function authorizeDeletionScope(
        Collection $departments,
        ?Collection $employees = null,
        ?Collection $jobTitles = null
    ): void {
        $user = auth()->user();
        if (! $user) {
            throw new AuthorizationException;
        }

        foreach ($departments as $department) {
            Gate::authorize('delete', $department);
        }

        if ($user->isSuperAdmin()) {
            return;
        }

        $authorizedCompanyIds = array_map('intval', CompanyFormService::getAuthorizedCompanyIds($user));
        if ($authorizedCompanyIds === []) {
            throw new AuthorizationException;
        }

        $visibleIds = CompanyAccessScopeService::apply(Department::query())
            ->whereKey($departments->pluck('id'))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($departments as $department) {
            if (! in_array((int) $department->id, $visibleIds, true)) {
                throw new AuthorizationException;
            }

            if (DepartmentHierarchyService::levelOf($department) < DepartmentHierarchyService::MAX_DEPTH) {
                throw new AuthorizationException;
            }

            $deptCompanyIds = DepartmentHierarchyService::companyIds($department);
            if ($deptCompanyIds === [] || array_diff($deptCompanyIds, $authorizedCompanyIds) !== []) {
                throw new AuthorizationException;
            }
        }

        foreach ($employees ?? collect() as $employee) {
            if ($employee->company_id !== null && ! in_array((int) $employee->company_id, $authorizedCompanyIds, true)) {
                throw new AuthorizationException;
            }
        }

        foreach ($jobTitles ?? collect() as $jobTitle) {
            if (! $jobTitle->is_global && $jobTitle->company_id !== null && ! in_array((int) $jobTitle->company_id, $authorizedCompanyIds, true)) {
                throw new AuthorizationException;
            }
        }
    }

    /**
     * Authorize that a non-super-admin user can transfer records into the target department.
     */
    public static function authorizeTargetScope(Department $target): void
    {
        $user = auth()->user();
        if (! $user) {
            throw new AuthorizationException;
        }

        if ($user->isSuperAdmin()) {
            return;
        }

        $authorizedCompanyIds = array_map('intval', CompanyFormService::getAuthorizedCompanyIds($user));
        $targetCompanyIds = DepartmentHierarchyService::companyIds($target);

        if ($authorizedCompanyIds === [] || $targetCompanyIds === [] || array_diff($targetCompanyIds, $authorizedCompanyIds) !== []) {
            throw new AuthorizationException;
        }
    }

    /**
     * Detect active employees or child job titles outside the branch that still reference job titles inside it.
     *
     * @param  Collection<int, JobTitle>  $jobTitles
     * @param  array<int, int>  $allDepartmentIds
     */
    public static function hasExternalJobTitleLinks(Collection $jobTitles, array $allDepartmentIds): bool
    {
        if ($jobTitles->isEmpty() || ! Schema::hasTable('job_titles')) {
            return false;
        }

        $jobTitleIds = $jobTitles->pluck('id')->map(fn ($id) => (int) $id)->all();

        $hasExternalChildTitles = JobTitle::query()
            ->whereIn('parent_id', $jobTitleIds)
            ->where(fn ($q) => $q->whereNull('department_id')->orWhereNotIn('department_id', $allDepartmentIds))
            ->exists();

        if ($hasExternalChildTitles) {
            return true;
        }

        if (Schema::hasColumn('employees', 'job_title_id')) {
            $hasExternalEmployees = Employee::query()
                ->whereIn('job_title_id', $jobTitleIds)
                ->where(fn ($q) => $q->whereNull('department_id')->orWhereNotIn('department_id', $allDepartmentIds))
                ->exists();

            if ($hasExternalEmployees) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, int>  $selectedIds
     * @return array<int, int>
     */
    public static function deduplicateRootIds(array $selectedIds): array
    {
        $selectedSet = array_flip($selectedIds);
        $roots = [];

        foreach ($selectedIds as $id) {
            $dept = Department::query()->find($id, ['id', 'parent_id']);
            if (! $dept) {
                continue;
            }

            $ancestors = DepartmentHierarchyService::ancestorIds($dept);
            $hasSelectedAncestor = false;
            foreach ($ancestors as $ancestorId) {
                if (isset($selectedSet[$ancestorId])) {
                    $hasSelectedAncestor = true;
                    break;
                }
            }

            if (! $hasSelectedAncestor) {
                $roots[] = (int) $id;
            }
        }

        return array_values(array_unique($roots));
    }

    /**
     * @param  array<int, int>  $rootIds
     * @return array<int, int>
     */
    public static function collectSubtreeIds(array $rootIds): array
    {
        $all = $rootIds;
        foreach ($rootIds as $rootId) {
            $all = array_merge($all, DepartmentHierarchyService::descendantIds($rootId));
        }

        return array_values(array_unique(array_map('intval', $all)));
    }

    /**
     * @param  array<int, int>  $employeeIds
     */
    public static function countPreservedFinancialRecords(array $employeeIds): int
    {
        if ($employeeIds === []) {
            return 0;
        }

        $total = 0;
        foreach (['payroll_records', 'installments', 'invoices'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'employee_id')) {
                $total += DB::table($table)->whereIn('employee_id', $employeeIds)->count();
            }
        }

        return $total;
    }

    /**
     * @param  Collection<int, Department>  $departments
     * @param  Collection<int, Employee>  $employees
     * @param  Collection<int, JobTitle>  $jobTitles
     * @param  array<int, int>  $allDepartmentIds
     * @return array<int, string>
     */
    public static function collectWarnings(
        Collection $departments,
        Collection $employees,
        Collection $jobTitles,
        array $allDepartmentIds
    ): array {
        $warnings = [];

        if ($departments->contains(fn (Department $d) => $d->manager_id !== null)) {
            $warnings[] = __('organization.departments.deletion.warnings.has_department_manager');
        }

        if ($jobTitles->isNotEmpty() && Schema::hasTable('job_titles')) {
            $hasExternalChildTitles = JobTitle::query()
                ->whereIn('parent_id', $jobTitles->pluck('id')->all())
                ->where(fn ($q) => $q->whereNull('department_id')->orWhereNotIn('department_id', $allDepartmentIds))
                ->exists();

            if ($hasExternalChildTitles) {
                $warnings[] = __('organization.departments.deletion.warnings.external_job_title_children');
            }
        }

        if ($employees->isNotEmpty() && Schema::hasTable('financial_approval_requests')) {
            $employeeIds = $employees->pluck('id')->all();
            $userIds = $employees->pluck('user_id')->filter()->all();
            $hasPendingApprovals = DB::table('financial_approval_requests')
                ->whereIn('status', ['pending_treasury', 'pending_manager'])
                ->where(function ($q) use ($allDepartmentIds, $employeeIds, $userIds): void {
                    $q->whereIn('treasury_reviewer_department_id', $allDepartmentIds)
                        ->orWhereIn('manager_department_id', $allDepartmentIds);
                    if ($employeeIds !== []) {
                        $q->orWhereIn('manager_employee_id', $employeeIds);
                    }
                    if ($userIds !== []) {
                        $q->orWhereIn('treasury_reviewer_id', $userIds)
                            ->orWhereIn('manager_user_id', $userIds);
                    }
                })
                ->exists();

            if ($hasPendingApprovals) {
                $warnings[] = __('organization.departments.deletion.warnings.pending_financial_approvals');
            }
        }

        return $warnings;
    }

    /**
     * @param  Collection<int, Department>  $departments
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    public static function sortIdsTopDown(Collection $departments, array $ids): array
    {
        $idSet = array_flip($ids);
        $roots = [];
        $childrenMap = [];

        foreach ($ids as $id) {
            $dept = $departments->get($id);
            if (! $dept || ! isset($idSet[(int) $dept->parent_id])) {
                $roots[] = (int) $id;
            } else {
                $childrenMap[(int) $dept->parent_id][] = (int) $id;
            }
        }

        $ordered = [];
        $queue = $roots;
        while ($queue !== []) {
            $current = array_shift($queue);
            $ordered[] = $current;
            foreach ($childrenMap[$current] ?? [] as $childId) {
                $queue[] = $childId;
            }
        }

        return $ordered;
    }
}
