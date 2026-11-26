<?php

namespace App\Services\Payroll;

use App\Enums\Payroll\FinancialApprovalItemStatus;
use App\Enums\Payroll\FinancialApprovalStatus;
use App\Models\Department;
use App\Models\Payroll\FinancialApprovalRequest;
use App\Models\Payroll\PayrollRecord;
use App\Models\User;
use App\Services\Departments\DepartmentManagerResolverService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PayrollApprovalScopeService
{
    /**
     * Collect the given department ID and all descendant department IDs downward.
     *
     * @return array<int, int>
     */
    public static function getDepartmentSubtreeIds(Department $department): array
    {
        $ids = [];
        $frontier = [(int) $department->id];
        $seen = [(int) $department->id => true];

        while ($frontier !== []) {
            foreach ($frontier as $id) {
                $ids[] = $id;
            }

            $children = Department::query()
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $frontier = [];
            foreach ($children as $childId) {
                if (! isset($seen[$childId])) {
                    $seen[$childId] = true;
                    $frontier[] = $childId;
                }
            }
        }

        return $ids;
    }

    /**
     * Authorize that the submitter can submit a payroll record within their company, branch, and permission scope.
     */
    public static function authorizeSubmitterForRecord(User $submitter, PayrollRecord $record): void
    {
        if (! DepartmentManagerResolverService::isActiveStatus($submitter->status)
            || (! $submitter->isSuperAdmin() && ! $submitter->hasPermissionTo('submit_financial_request'))) {
            throw new AuthorizationException(__('financial.errors.unauthorized_submit_request'));
        }

        if ($submitter->isSuperAdmin()) {
            return;
        }

        $authorizedCompanyIds = array_map('intval', $submitter->getAccessibleCompanyIds());
        if ($authorizedCompanyIds === [] || ! in_array((int) $record->company_id, $authorizedCompanyIds, true)) {
            throw new AuthorizationException(__('financial.errors.out_of_company_scope'));
        }

        if ($submitter->branch_id && ! $submitter->canAccessAllBranches()) {
            $branchId = $record->metadata['branch_id_snapshot'] ?? $record->employee?->branch_id;
            if (! $branchId || (int) $submitter->branch_id !== (int) $branchId) {
                throw new AuthorizationException(__('financial.errors.out_of_branch_scope'));
            }
        }
    }

    public static function canSubmitPayrollRecord(User $submitter, PayrollRecord $record): bool
    {
        try {
            static::authorizeSubmitterForRecord($submitter, $record);

            return true;
        } catch (AuthorizationException) {
            return false;
        }
    }

    /**
     * Return all company IDs covered by the treasury reviewer's department subtree.
     * Returns an empty array if the user lacks treasury review eligibility or a valid Layer 1 manager path.
     *
     * @return array<int, int>
     */
    public static function getReviewerCoveredCompanyIds(User $user): array
    {
        if (! static::isEligibleTreasuryReviewer($user)) {
            return [];
        }

        $resolved = DepartmentManagerResolverService::resolveForUser($user);
        if (! $resolved['ok'] || ! $resolved['department']) {
            return [];
        }

        $subtreeIds = static::getDepartmentSubtreeIds($resolved['department']);
        if ($subtreeIds === []) {
            return [];
        }

        $departments = Department::query()
            ->with('companies:id')
            ->whereIn('id', $subtreeIds)
            ->get();

        $companyIds = [];
        foreach ($departments as $dept) {
            foreach ($dept->getAssignedCompanyIds() as $companyId) {
                $companyIds[(int) $companyId] = (int) $companyId;
            }
        }

        return array_values($companyIds);
    }

    public static function isEligibleTreasuryReviewer(User $user): bool
    {
        if (! DepartmentManagerResolverService::isActiveStatus($user->status)) {
            return false;
        }

        if (! $user->hasPermissionTo('update_treasury_transaction')) {
            return false;
        }

        return DepartmentManagerResolverService::resolveForUser($user)['ok'];
    }

    /**
     * @param  array<int, int>  $companyIds
     */
    public static function coversAllCompanies(User $user, array $companyIds): bool
    {
        $normalized = array_values(array_unique(array_filter(array_map('intval', $companyIds))));
        if ($normalized === []) {
            return false;
        }

        $covered = static::getReviewerCoveredCompanyIds($user);
        if ($covered === []) {
            return false;
        }

        return array_diff($normalized, $covered) === [];
    }

    /**
     * Find all active treasury reviewers whose department subtree covers every company in the request.
     *
     * @param  array<int, int>  $companyIds
     * @return Collection<int, User>
     */
    public static function findEligibleReviewers(array $companyIds): Collection
    {
        $normalized = array_values(array_unique(array_filter(array_map('intval', $companyIds))));
        if ($normalized === []) {
            return collect();
        }

        return User::query()
            ->where('status', 'active')
            ->whereHas('employee', fn ($q) => $q->where('status', 'active')->whereNotNull('department_id'))
            ->get()
            ->filter(fn (User $user): bool => static::coversAllCompanies($user, $normalized))
            ->values();
    }

    /**
     * Build detailed diagnostic info explaining why no treasury reviewer matched the requested companies.
     *
     * @param  array<int, int>  $companyIds
     * @return array<int, array<string, mixed>>
     */
    public static function diagnoseReviewerEligibility(array $companyIds): array
    {
        $normalized = array_values(array_unique(array_filter(array_map('intval', $companyIds))));

        $candidates = User::query()
            ->with(['employee.department'])
            ->where(function (Builder $q): void {
                $q->whereHas('roles.permissions', fn ($p) => $p->where('name', 'update_treasury_transaction'))
                    ->orWhereHas('permissions', fn ($p) => $p->where('name', 'update_treasury_transaction'));
            })
            ->get();

        if ($candidates->isEmpty()) {
            return [['reason' => 'no_users_have_update_treasury_transaction_permission']];
        }

        return $candidates->map(function (User $user) use ($normalized): array {
            $employee = $user->employee;
            $resolved = DepartmentManagerResolverService::resolveForUser($user);
            $covered = [];

            if ($resolved['ok'] && $resolved['department']) {
                $subtreeIds = static::getDepartmentSubtreeIds($resolved['department']);
                $departments = Department::query()->with('companies:id')->whereIn('id', $subtreeIds)->get();
                foreach ($departments as $dept) {
                    foreach ($dept->getAssignedCompanyIds() as $cid) {
                        $covered[(int) $cid] = (int) $cid;
                    }
                }
            }
            $coveredIds = array_values($covered);
            $missingIds = array_values(array_diff($normalized, $coveredIds));

            $reason = match (true) {
                ! DepartmentManagerResolverService::isActiveStatus($user->status) => 'user_inactive',
                ! $user->hasPermissionTo('update_treasury_transaction') => 'missing_update_treasury_transaction',
                ! $employee => 'no_employee_profile',
                ! DepartmentManagerResolverService::isActiveStatus($employee->status) => 'employee_inactive',
                ! $employee->department_id => 'employee_has_no_department',
                ! $resolved['ok'] => 'manager_path_error:'.($resolved['error'] ?? 'unknown'),
                $missingIds !== [] => 'department_subtree_missing_companies',
                default => 'eligible',
            };

            return [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_status' => $user->status,
                'employee_id' => $employee?->id,
                'employee_status' => $employee?->status?->value ?? $employee?->status,
                'department_id' => $employee?->department_id,
                'department_name' => $employee?->department?->name,
                'manager_path_ok' => $resolved['ok'],
                'manager_path_error' => $resolved['error'],
                'root_department_id' => $resolved['root_department']?->id,
                'manager_employee_id' => $resolved['manager_employee']?->id,
                'covered_company_ids' => $coveredIds,
                'missing_company_ids' => $missingIds,
                'disqualification_reason' => $reason,
            ];
        })->values()->all();
    }

    /**
     * Re-validate the actual reviewer, their department coverage, and their Layer 1 manager path.
     *
     * @return array{ok: bool, reason: ?string, resolved: ?array<string, mixed>}
     */
    public static function validatePendingManagerPath(FinancialApprovalRequest $request): array
    {
        if (! $request->treasury_reviewer_id || ! $request->manager_department_id || ! $request->manager_user_id) {
            return ['ok' => false, 'reason' => 'missing_reviewer_or_manager_route', 'resolved' => null];
        }

        $reviewer = User::query()->with('employee.department')->find($request->treasury_reviewer_id);
        if (! $reviewer || ! static::isEligibleTreasuryReviewer($reviewer)) {
            return ['ok' => false, 'reason' => 'reviewer_no_longer_eligible', 'resolved' => null];
        }

        $companyIds = $request->relationLoaded('companies')
            ? $request->companies->modelKeys()
            : $request->companies()->pluck('companies.id')->all();

        if (! static::coversAllCompanies($reviewer, $companyIds)) {
            return ['ok' => false, 'reason' => 'reviewer_coverage_changed', 'resolved' => null];
        }

        $resolved = DepartmentManagerResolverService::resolveForUser($reviewer);
        if (! $resolved['ok'] || ! $resolved['root_department'] || ! $resolved['manager_user']) {
            return ['ok' => false, 'reason' => 'manager_path_unavailable', 'resolved' => null];
        }

        if ((int) $resolved['department']?->id !== (int) $request->treasury_reviewer_department_id
            || (int) $resolved['root_department']->id !== (int) $request->manager_department_id
            || (int) $resolved['manager_user']->id !== (int) $request->manager_user_id) {
            return ['ok' => false, 'reason' => 'approval_route_changed', 'resolved' => $resolved];
        }

        return ['ok' => true, 'reason' => null, 'resolved' => $resolved];
    }

    public static function isAssignedManager(User $user, FinancialApprovalRequest $request): bool
    {
        if (! DepartmentManagerResolverService::isActiveStatus($user->status)) {
            return false;
        }

        if (! $user->hasPermissionTo('approve_financial_request')) {
            return false;
        }

        if ((int) $request->manager_user_id !== (int) $user->id) {
            return false;
        }

        if ($request->status === FinancialApprovalStatus::PENDING_MANAGER) {
            return static::validatePendingManagerPath($request)['ok'];
        }

        return true;
    }

    public static function canRerouteRequest(User $user, FinancialApprovalRequest $request): bool
    {
        if (! DepartmentManagerResolverService::isActiveStatus($user->status)) {
            return false;
        }

        if (! in_array($request->status, [
            FinancialApprovalStatus::PENDING_TREASURY,
            FinancialApprovalStatus::PENDING_MANAGER,
        ], true)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->hasPermissionTo('submit_financial_request')) {
            return false;
        }

        $items = $request->items()
            ->where('version', $request->version)
            ->where('status', FinancialApprovalItemStatus::PENDING->value)
            ->with('payrollRecord.employee')
            ->get();

        if ($items->isEmpty()) {
            return false;
        }

        foreach ($items as $item) {
            if (! $item->payrollRecord || ! static::canSubmitPayrollRecord($user, $item->payrollRecord)) {
                return false;
            }
        }

        return true;
    }

    public static function canViewRequest(User $user, FinancialApprovalRequest $request): bool
    {
        if (! DepartmentManagerResolverService::isActiveStatus($user->status)) {
            return false;
        }

        if ((int) $request->submitted_by === (int) $user->id && $user->hasPermissionTo('view_financial_request')) {
            return true;
        }

        if ($request->status === FinancialApprovalStatus::PENDING_TREASURY) {
            $companyIds = $request->relationLoaded('companies')
                ? $request->companies->modelKeys()
                : $request->companies()->pluck('companies.id')->all();

            return static::coversAllCompanies($user, $companyIds);
        }

        if ($request->status === FinancialApprovalStatus::PENDING_MANAGER) {
            if (static::isAssignedManager($user, $request)) {
                return true;
            }

            if ((int) $request->treasury_reviewer_id === (int) $user->id) {
                $companyIds = $request->relationLoaded('companies')
                    ? $request->companies->modelKeys()
                    : $request->companies()->pluck('companies.id')->all();

                return static::coversAllCompanies($user, $companyIds);
            }

            return false;
        }

        if ((int) $request->treasury_reviewer_id === (int) $user->id && $user->hasPermissionTo('update_treasury_transaction')) {
            return true;
        }

        if ((int) $request->manager_user_id === (int) $user->id && $user->hasPermissionTo('approve_financial_request')) {
            return true;
        }

        return false;
    }

    /**
     * Scope a FinancialApprovalRequest query using the exact same authorization rules as canViewRequest().
     */
    public static function scopeVisibleRequests(Builder $query, User $user): Builder
    {
        if (! DepartmentManagerResolverService::isActiveStatus($user->status)) {
            return $query->whereRaw('1 = 0');
        }

        $coveredCompanyIds = static::getReviewerCoveredCompanyIds($user);

        return $query->where(function (Builder $q) use ($user, $coveredCompanyIds): void {
            $hasCondition = false;

            if ($user->hasPermissionTo('view_financial_request')) {
                $q->where('submitted_by', $user->id);
                $hasCondition = true;
            }

            if ($coveredCompanyIds !== []) {
                $method = $hasCondition ? 'orWhere' : 'where';
                $q->{$method}(function (Builder $pendingTreasury) use ($coveredCompanyIds): void {
                    $pendingTreasury->where('status', FinancialApprovalStatus::PENDING_TREASURY->value)
                        ->whereHas('companies')
                        ->whereDoesntHave('companies', fn (Builder $c) => $c->whereNotIn('companies.id', $coveredCompanyIds));
                });
                $hasCondition = true;

                $q->orWhere(function (Builder $pendingManagerReviewer) use ($user, $coveredCompanyIds): void {
                    $pendingManagerReviewer->where('status', FinancialApprovalStatus::PENDING_MANAGER->value)
                        ->where('treasury_reviewer_id', $user->id)
                        ->whereHas('companies')
                        ->whereDoesntHave('companies', fn (Builder $c) => $c->whereNotIn('companies.id', $coveredCompanyIds));
                });
            }

            if ($user->hasPermissionTo('update_treasury_transaction')) {
                $method = $hasCondition ? 'orWhere' : 'where';
                $q->{$method}(function (Builder $historicalReviewer) use ($user): void {
                    $historicalReviewer->where('treasury_reviewer_id', $user->id)
                        ->whereNotIn('status', [
                            FinancialApprovalStatus::PENDING_TREASURY->value,
                            FinancialApprovalStatus::PENDING_MANAGER->value,
                        ]);
                });
                $hasCondition = true;
            }

            if ($user->hasPermissionTo('approve_financial_request')) {
                $method = $hasCondition ? 'orWhere' : 'where';
                $q->{$method}(function (Builder $historicalManager) use ($user): void {
                    $historicalManager->where('manager_user_id', $user->id)
                        ->where('status', '!=', FinancialApprovalStatus::PENDING_MANAGER->value);
                });
                $hasCondition = true;

                $pendingManagerIds = FinancialApprovalRequest::query()
                    ->where('status', FinancialApprovalStatus::PENDING_MANAGER->value)
                    ->where('manager_user_id', $user->id)
                    ->with('companies:id')
                    ->get()
                    ->filter(fn (FinancialApprovalRequest $req): bool => static::isAssignedManager($user, $req))
                    ->modelKeys();

                if ($pendingManagerIds !== []) {
                    $q->orWhereIn('id', $pendingManagerIds);
                }
            }

            if (! $hasCondition) {
                $q->whereRaw('1 = 0');
            }
        });
    }
}
