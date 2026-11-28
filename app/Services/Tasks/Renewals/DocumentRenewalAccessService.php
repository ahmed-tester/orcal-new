<?php

namespace App\Services\Tasks\Renewals;

use App\Enums\EmploymentType;
use App\Enums\GeneralStatus;
use App\Enums\UserType;
use App\Models\Department;
use App\Models\Employee;
use App\Models\UnifiedDeficiencyTask;
use App\Models\User;
use App\Services\CompanyFormService;
use App\Services\EmployeePerformanceAccessService;
use App\Services\OperationsAuditService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class DocumentRenewalAccessService
{
    /**
     * Resolve all company IDs covered by active Layer 3 subdepartments under
     * a given active Layer 1 administration. Never treats is_global as all companies.
     *
     * @return array<int, int>
     */
    public static function companyIdsCoveredByAdministration(Department $administration): array
    {
        if ($administration->parent_id !== null || ! self::isDepartmentActive($administration)) {
            return [];
        }

        $allActive = Department::query()
            ->where('status', GeneralStatus::ACTIVE->value)
            ->with('companies:id')
            ->get(['id', 'parent_id', 'company_id', 'is_global', 'status']);

        $childrenByParent = $allActive->groupBy('parent_id');
        $visited = [(int) $administration->id => true];
        $companyIds = [];

        // Layer 2 children
        foreach ($childrenByParent->get($administration->id, collect()) as $layer2) {
            if (isset($visited[(int) $layer2->id])) {
                continue;
            }
            $visited[(int) $layer2->id] = true;

            // Layer 3 children under Layer 2
            foreach ($childrenByParent->get($layer2->id, collect()) as $layer3) {
                if (isset($visited[(int) $layer3->id])) {
                    continue;
                }
                $visited[(int) $layer3->id] = true;

                foreach (self::assignedCompanyIdsForLayer3($layer3) as $cid) {
                    $companyIds[$cid] = $cid;
                }
            }
        }

        return array_values($companyIds);
    }

    /**
     * Resolve all company IDs covered by all active Layer 1 administrations
     * currently managed by the given employee.
     *
     * @return array<int, int>
     */
    public static function coveredCompanyIdsForManager(Employee $employee): array
    {
        if (! EmployeePerformanceAccessService::isEligibleSaudiTaskEmployee($employee)) {
            return [];
        }

        $administrations = Department::query()
            ->whereNull('parent_id')
            ->where('status', GeneralStatus::ACTIVE->value)
            ->where('manager_id', $employee->id)
            ->get();

        if ($administrations->isEmpty()) {
            return [];
        }

        $companyIds = [];
        foreach ($administrations as $administration) {
            foreach (self::companyIdsCoveredByAdministration($administration) as $cid) {
                $companyIds[$cid] = $cid;
            }
        }

        return array_values($companyIds);
    }

    /**
     * Build a request-scoped, zero-N+1 resolver for eligible managers across all
     * companies and document types. Eager-loads active departments, managers,
     * users, and permissions once.
     *
     * @return \Closure(int, string): Collection<int, Employee>
     */
    public static function buildEligibleManagersResolver(): \Closure
    {
        $allActive = Department::query()
            ->where('status', GeneralStatus::ACTIVE->value)
            ->with(['companies:id', 'manager.user.permissions:id,name', 'manager.user.roles.permissions:id,name'])
            ->get(['id', 'parent_id', 'manager_id', 'company_id', 'is_global', 'status']);

        $childrenByParent = $allActive->groupBy('parent_id');
        $roots = $allActive->whereNull('parent_id')->filter(fn (Department $d) => $d->manager_id !== null);

        $managerIds = $roots->pluck('manager_id')->filter()->unique()->values()->all();
        $eligibleSaudiIds = $managerIds !== []
            ? Employee::query()
                ->nonTemporary()
                ->saudi()
                ->where('status', GeneralStatus::ACTIVE->value)
                ->whereHas('user', fn ($u) => $u->whereNull('user_type')->orWhere('user_type', '!=', UserType::FOREIGN_EMPLOYEE->value))
                ->whereIn('id', $managerIds)
                ->pluck('id')
                ->flip()
                ->all()
            : [];

        $rootRecords = [];
        foreach ($roots as $root) {
            $manager = $root->manager;
            if (! $manager instanceof Employee || ! isset($eligibleSaudiIds[(int) $manager->id])) {
                continue;
            }

            $empStatus = $manager->status instanceof GeneralStatus ? $manager->status->value : (string) $manager->status;
            if (
                $manager->employment_type === EmploymentType::TEMPORARY
                || $empStatus !== GeneralStatus::ACTIVE->value
            ) {
                continue;
            }

            $user = $manager->user;
            if (! $user instanceof User || $user->user_type === UserType::FOREIGN_EMPLOYEE) {
                continue;
            }

            $directNames = $user->permissions->pluck('name')->all();
            $roleNames = $user->roles->flatMap(fn ($r) => $r->permissions->pluck('name'))->all();
            $explicitPerms = array_flip(array_unique(array_merge($directNames, $roleNames)));

            $coveredCompanies = [];
            $visited = [(int) $root->id => true];
            foreach ($childrenByParent->get($root->id, collect()) as $layer2) {
                if (isset($visited[(int) $layer2->id])) {
                    continue;
                }
                $visited[(int) $layer2->id] = true;
                foreach ($childrenByParent->get($layer2->id, collect()) as $layer3) {
                    if (isset($visited[(int) $layer3->id])) {
                        continue;
                    }
                    $visited[(int) $layer3->id] = true;
                    foreach (self::assignedCompanyIdsForLayer3($layer3) as $cid) {
                        $coveredCompanies[$cid] = true;
                    }
                }
            }

            if ($coveredCompanies === []) {
                continue;
            }

            $rootRecords[] = [
                'employee' => $manager,
                'perms' => $explicitPerms,
                'companies' => $coveredCompanies,
            ];
        }

        $memo = [];

        return static function (int $companyId, string $documentType) use (&$memo, $rootRecords): Collection {
            $cacheKey = $companyId.':'.$documentType;
            if (isset($memo[$cacheKey])) {
                return $memo[$cacheKey];
            }

            if ($companyId <= 0 || ! RenewalDocumentRegistry::isValidType($documentType)) {
                return $memo[$cacheKey] = collect();
            }

            $permission = RenewalDocumentRegistry::permissionFor($documentType);
            $matched = [];

            foreach ($rootRecords as $entry) {
                $empId = (int) $entry['employee']->id;
                if (isset($matched[$empId])) {
                    continue;
                }
                if (isset($entry['companies'][$companyId]) && isset($entry['perms'][$permission])) {
                    $matched[$empId] = $entry['employee'];
                }
            }

            return $memo[$cacheKey] = collect(array_values($matched));
        };
    }

    /**
     * Return all eligible Layer 1 manager employees who can claim a renewal request
     * for the given company and document type. Deduplicated by employee ID.
     * Does NOT use Gate::before / super_admin bypass.
     *
     * @return Collection<int, Employee>
     */
    public static function eligibleManagersFor(int $companyId, string $documentType): Collection
    {
        if ($companyId <= 0 || ! RenewalDocumentRegistry::isValidType($documentType)) {
            return collect();
        }

        $permission = RenewalDocumentRegistry::permissionFor($documentType);

        $allActive = Department::query()
            ->where('status', GeneralStatus::ACTIVE->value)
            ->with(['companies:id', 'manager.user'])
            ->get(['id', 'parent_id', 'manager_id', 'company_id', 'is_global', 'status']);

        $childrenByParent = $allActive->groupBy('parent_id');
        $roots = $allActive->whereNull('parent_id')->filter(fn (Department $d) => $d->manager_id !== null);

        $eligibleByEmployeeId = [];

        foreach ($roots as $root) {
            $manager = $root->manager;
            if (! $manager instanceof Employee) {
                continue;
            }

            if (isset($eligibleByEmployeeId[(int) $manager->id])) {
                continue;
            }

            if (! EmployeePerformanceAccessService::isEligibleSaudiTaskEmployee($manager)) {
                continue;
            }

            $user = $manager->user;
            if (! $user instanceof User || ! self::userHasExplicitRenewalPermission($user, $permission)) {
                continue;
            }

            if (! self::administrationCoversCompany($root, $companyId, $childrenByParent)) {
                continue;
            }

            $eligibleByEmployeeId[(int) $manager->id] = $manager;
        }

        return collect(array_values($eligibleByEmployeeId));
    }

    /**
     * Check whether the user holds the renewal permission explicitly
     * (direct or role-granted), ignoring Gate::before super_admin bypass.
     */
    public static function userHasExplicitRenewalPermission(User $user, string $permission): bool
    {
        if (! RenewalDocumentRegistry::isRenewalPermission($permission)) {
            return false;
        }

        if ($user->permissions()->where('name', $permission)->exists()) {
            return true;
        }

        return $user->roles()
            ->whereHas('permissions', fn ($q) => $q->where('name', $permission))
            ->exists();
    }

    /**
     * @return array<int, string>
     */
    public static function directRenewalPermissions(User $user): array
    {
        return $user->permissions()
            ->whereIn('name', RenewalDocumentRegistry::allPermissionKeys())
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function roleInheritedRenewalPermissions(User $user): array
    {
        return $user->roles()
            ->with(['permissions' => fn ($q) => $q->whereIn('name', RenewalDocumentRegistry::allPermissionKeys())])
            ->get()
            ->flatMap(fn ($role) => $role->permissions->pluck('name'))
            ->unique()
            ->values()
            ->all();
    }

    public static function canClaim(User $user, UnifiedDeficiencyTask $request): bool
    {
        if (! $request->isDocumentRenewal()) {
            return false;
        }

        if ($request->status !== UnifiedDeficiencyTask::RENEWAL_STATUS_AVAILABLE || $request->responsible_employee_id !== null) {
            return false;
        }

        $employee = $user->employee;
        if (! $employee instanceof Employee || ! EmployeePerformanceAccessService::isEligibleSaudiTaskEmployee($employee)) {
            return false;
        }

        $documentType = (string) ($request->metadata['document_type'] ?? '');
        if (! RenewalDocumentRegistry::isValidType($documentType)) {
            return false;
        }

        if (! self::userHasExplicitRenewalPermission($user, RenewalDocumentRegistry::permissionFor($documentType))) {
            return false;
        }

        return in_array((int) $request->company_id, self::coveredCompanyIdsForManager($employee), true);
    }

    public static function canEditRenewalWork(User $user, UnifiedDeficiencyTask $request): bool
    {
        if (! $request->isDocumentRenewal()) {
            return false;
        }

        if (! in_array($request->status, [
            UnifiedDeficiencyTask::RENEWAL_STATUS_CLAIMED,
            UnifiedDeficiencyTask::RENEWAL_STATUS_RETURNED,
        ], true)) {
            return false;
        }

        $employee = $user->employee;
        if (! $employee instanceof Employee || (int) $employee->id !== (int) $request->responsible_employee_id) {
            return false;
        }

        if (! EmployeePerformanceAccessService::isEligibleSaudiTaskEmployee($employee)) {
            return false;
        }

        $documentType = (string) ($request->metadata['document_type'] ?? '');
        if (! RenewalDocumentRegistry::isValidType($documentType)) {
            return false;
        }

        if (! self::userHasExplicitRenewalPermission($user, RenewalDocumentRegistry::permissionFor($documentType))) {
            return false;
        }

        return in_array((int) $request->company_id, self::coveredCompanyIdsForManager($employee), true);
    }

    public static function isUserActive(User $user): bool
    {
        $status = $user->status instanceof GeneralStatus ? $user->status->value : (string) $user->status;

        return $status === GeneralStatus::ACTIVE->value;
    }

    public static function userRestrictedBranchId(User $user): ?int
    {
        if ($user->isSuperAdmin() || (method_exists($user, 'canAccessAllBranches') && $user->canAccessAllBranches())) {
            return null;
        }

        $branchId = $user->employee?->branch_id ?? $user->branch_id;

        return $branchId !== null ? (int) $branchId : null;
    }

    public static function canApproveTreasury(User $user, UnifiedDeficiencyTask $request): bool
    {
        if (! $request->isDocumentRenewal() || $request->status !== UnifiedDeficiencyTask::RENEWAL_STATUS_PENDING_TREASURY) {
            return false;
        }

        if (! self::isUserActive($user)) {
            return false;
        }

        // Strict no self-approval check
        $request->loadMissing('responsibleEmployee');
        if ((int) ($request->responsibleEmployee?->user_id ?? 0) === (int) $user->id) {
            return false;
        }

        if ((int) ($request->metadata['submitted_by_user_id'] ?? 0) === (int) $user->id) {
            return false;
        }

        $hasTreasuryPermission = $user->isSuperAdmin() || $user->can('update_treasury_transaction');
        if (! $hasTreasuryPermission) {
            return false;
        }

        $authorizedCompanyIds = CompanyFormService::getAuthorizedCompanyIds($user);
        if (! in_array((int) $request->company_id, $authorizedCompanyIds, true)) {
            return false;
        }

        $requestBranchId = filled($request->metadata['branch_id'] ?? null)
            ? (int) $request->metadata['branch_id']
            : null;
        $userBranchId = self::userRestrictedBranchId($user);

        if ($requestBranchId !== null && $userBranchId !== null && $userBranchId !== $requestBranchId) {
            return false;
        }

        return true;
    }

    /**
     * @return Collection<int, User>
     */
    public static function eligibleTreasuryReviewers(int $companyId, ?int $excludeUserId = null, ?int $branchId = null): Collection
    {
        $candidates = User::query()
            ->where('status', GeneralStatus::ACTIVE->value)
            ->when($excludeUserId !== null, fn ($q) => $q->where('id', '!=', $excludeUserId))
            ->where(function ($query): void {
                $query->whereHas('roles', fn ($r) => $r->whereIn('name', ['super_admin', 'Super Admin', 'مدير النظام']))
                    ->orWhereHas('permissions', fn ($p) => $p->where('name', 'update_treasury_transaction'))
                    ->orWhereHas('roles.permissions', fn ($p) => $p->where('name', 'update_treasury_transaction'));
            })
            ->with('employee.department')
            ->get();

        return $candidates->filter(function (User $candidate) use ($companyId, $branchId): bool {
            if (! ($candidate->isSuperAdmin() || $candidate->can('update_treasury_transaction'))) {
                return false;
            }

            if (! in_array($companyId, CompanyFormService::getAuthorizedCompanyIds($candidate), true)) {
                return false;
            }

            if ($branchId !== null) {
                $userBranchId = self::userRestrictedBranchId($candidate);
                if ($userBranchId !== null && $userBranchId !== $branchId) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    public static function canReassign(User $user, UnifiedDeficiencyTask $request): bool
    {
        if (! $request->isDocumentRenewal()) {
            return false;
        }

        if (in_array($request->status, [
            UnifiedDeficiencyTask::RENEWAL_STATUS_COMPLETED,
            UnifiedDeficiencyTask::RENEWAL_STATUS_CANCELLED,
        ], true)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->isOwner() && in_array((int) $request->company_id, CompanyFormService::getAuthorizedCompanyIds($user), true);
    }

    public static function canViewRequest(User $user, UnifiedDeficiencyTask $request): bool
    {
        if (! $request->isDocumentRenewal()) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->employee && (int) $user->employee->id === (int) $request->responsible_employee_id) {
            return true;
        }

        if (self::canClaim($user, $request)) {
            return true;
        }

        if (self::canApproveTreasury($user, $request)) {
            return true;
        }

        if (($user->isOwner() || $user->can('update_treasury_transaction'))
            && in_array((int) $request->company_id, CompanyFormService::getAuthorizedCompanyIds($user), true)) {
            $requestBranchId = filled($request->metadata['branch_id'] ?? null)
                ? (int) $request->metadata['branch_id']
                : null;
            $userBranchId = self::userRestrictedBranchId($user);

            if ($requestBranchId !== null && $userBranchId !== null && $userBranchId !== $requestBranchId) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Synchronize Layer 1 manager renewal permissions safely (whitelist-only, never syncPermissions()).
     * Also handles manager replacement per Section 6 & 17.5.
     *
     * @param  array<int, string>  $selectedPermissions
     * @return array{
     *     transferred: array<int, string>,
     *     granted: array<int, string>,
     *     revoked_from_old: array<int, string>,
     *     kept_for_old_other_admin: bool,
     *     old_role_inherited: array<int, string>,
     *     new_role_inherited: array<int, string>
     * }
     */
    public static function syncLayerOneManagerPermissions(
        Department $department,
        ?int $oldManagerId,
        ?int $newManagerId,
        ?array $selectedPermissions = null,
        ?int $actorUserId = null,
    ): array {
        $result = [
            'transferred' => [],
            'granted' => [],
            'revoked_from_old' => [],
            'kept_for_old_other_admin' => false,
            'old_role_inherited' => [],
            'new_role_inherited' => [],
        ];

        if ($department->parent_id !== null) {
            return $result;
        }

        $whitelist = RenewalDocumentRegistry::allPermissionKeys();
        RenewalDocumentRegistry::syncPermissionDefinitions();

        return DB::transaction(function () use ($department, $oldManagerId, $newManagerId, $selectedPermissions, $actorUserId, $whitelist, $result): array {
            $oldUser = $oldManagerId ? Employee::with('user')->find($oldManagerId)?->user : null;
            $newUser = $newManagerId ? Employee::with('user')->find($newManagerId)?->user : null;

            $managerChanged = $oldManagerId !== $newManagerId;

            if ($oldUser instanceof User) {
                $result['old_role_inherited'] = self::roleInheritedRenewalPermissions($oldUser);
            }
            if ($newUser instanceof User) {
                $result['new_role_inherited'] = self::roleInheritedRenewalPermissions($newUser);
            }

            if ($managerChanged && $oldUser instanceof User) {
                $oldDirect = self::directRenewalPermissions($oldUser);

                if ($newUser instanceof User && $oldDirect !== []) {
                    foreach ($oldDirect as $perm) {
                        if (! $newUser->hasDirectPermission($perm)) {
                            $newUser->givePermissionTo($perm);
                        }
                    }
                    $result['transferred'] = $oldDirect;
                }

                $stillManagesOtherActiveRoot = Department::query()
                    ->whereNull('parent_id')
                    ->where('status', GeneralStatus::ACTIVE->value)
                    ->where('manager_id', $oldManagerId)
                    ->where('id', '!=', $department->id)
                    ->exists();

                if ($stillManagesOtherActiveRoot) {
                    $result['kept_for_old_other_admin'] = true;
                } elseif ($oldDirect !== []) {
                    foreach ($oldDirect as $perm) {
                        $oldUser->revokePermissionTo($perm);
                    }
                    $result['revoked_from_old'] = $oldDirect;
                }
            }

            if ($newUser instanceof User && $selectedPermissions !== null) {
                $desired = array_values(array_intersect($whitelist, $selectedPermissions));
                if ($managerChanged && $result['transferred'] !== []) {
                    $desired = array_values(array_unique(array_merge($desired, $result['transferred'])));
                }
                $currentDirect = self::directRenewalPermissions($newUser);

                $toGrant = array_values(array_diff($desired, $currentDirect));
                $toRevoke = array_values(array_diff($currentDirect, $desired));

                foreach ($toGrant as $perm) {
                    $newUser->givePermissionTo($perm);
                }
                foreach ($toRevoke as $perm) {
                    $newUser->revokePermissionTo($perm);
                }

                $result['granted'] = $toGrant;
                if (! $managerChanged && $toRevoke !== []) {
                    $result['revoked_from_old'] = $toRevoke;
                }
            }

            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            $auditCompanyId = self::companyIdsCoveredByAdministration($department)[0] ?? null;
            if ($auditCompanyId !== null) {
                OperationsAuditService::record(
                    companyId: $auditCompanyId,
                    transactionId: null,
                    actionType: 'renewal_permissions_synced',
                    oldStatus: $oldManagerId ? "manager:{$oldManagerId}" : null,
                    newStatus: $newManagerId ? "manager:{$newManagerId}" : null,
                    payload: array_merge($result, [
                        'department_id' => $department->id,
                        'department_code' => $department->department_code,
                    ]),
                    userId: $actorUserId ?? auth()->id(),
                );
            }

            return $result;
        });
    }

    private static function isDepartmentActive(Department $department): bool
    {
        $status = $department->status instanceof GeneralStatus ? $department->status->value : (string) $department->status;

        return $status === GeneralStatus::ACTIVE->value && ! $department->trashed();
    }

    /**
     * @return array<int, int>
     */
    private static function assignedCompanyIdsForLayer3(Department $layer3): array
    {
        $pivotIds = $layer3->relationLoaded('companies')
            ? $layer3->companies->pluck('id')->map(fn ($id) => (int) $id)->all()
            : $layer3->companies()->pluck('companies.id')->map(fn ($id) => (int) $id)->all();

        if ($pivotIds !== []) {
            return array_values(array_unique($pivotIds));
        }

        if ($layer3->is_global) {
            return [];
        }

        return $layer3->company_id ? [(int) $layer3->company_id] : [];
    }

    /**
     * @param  Collection<int|string, Collection<int, Department>>  $childrenByParent
     */
    private static function administrationCoversCompany(Department $root, int $companyId, Collection $childrenByParent): bool
    {
        $visited = [(int) $root->id => true];

        foreach ($childrenByParent->get($root->id, collect()) as $layer2) {
            if (isset($visited[(int) $layer2->id])) {
                continue;
            }
            $visited[(int) $layer2->id] = true;

            foreach ($childrenByParent->get($layer2->id, collect()) as $layer3) {
                if (isset($visited[(int) $layer3->id])) {
                    continue;
                }
                $visited[(int) $layer3->id] = true;

                if (in_array($companyId, self::assignedCompanyIdsForLayer3($layer3), true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
