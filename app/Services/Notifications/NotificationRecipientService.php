<?php

namespace App\Services\Notifications;

use App\Enums\UserType;
use App\Models\CashHandover;
use App\Models\Company;
use App\Models\Debt;
use App\Models\Employee;
use App\Models\ForeignEmployee;
use App\Models\Installment;
use App\Models\InstallmentPayment;
use App\Models\Invoice;
use App\Models\Leave;
use App\Models\SaudiEmployee;
use App\Models\ServiceTransaction;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Violation;
use App\Services\CompanyFormService;
use App\Services\CashHandoverService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class NotificationRecipientService
{
    /**
     * Resolve eligible recipients who are active, authorized to update the record,
     * and scoped within the record's company and branch.
     *
     * @param  Model  $record  The entity related to the notification
     * @param  string|array  $requiredPermissions  Explicit update permission(s)
     * @param  int|null  $companyId  Override company ID if different from record
     * @param  int|null  $branchId  Override branch ID if different from record
     * @param  array<int>  $excludeUserIds  User IDs to exclude (e.g. self-actions)
     * @return Collection<int, User>
     */
    public static function getQualifiedRecipients(
        Model $record,
        string|array $requiredPermissions = [],
        ?int $companyId = null,
        ?int $branchId = null,
        array $excludeUserIds = []
    ): Collection {
        // 1. Resolve required permissions
        $permissions = (array) $requiredPermissions;
        if (empty($permissions)) {
            $permissions = static::resolveDefaultUpdatePermissions($record);
        }

        // 2. Resolve target company and branch from record
        $targetCompanyId = $companyId ?? static::resolveCompanyId($record);
        $targetBranchId = $branchId ?? static::resolveBranchId($record);

        // 3. Fetch active users only
        $candidates = User::query()
            ->where('status', 'active')
            ->with(['employee.department', 'owners', 'roles.permissions', 'permissions'])
            ->get();

        $qualified = collect();

        foreach ($candidates as $user) {
            // Exclude specified IDs (e.g., author who performed the action)
            if (in_array($user->id, $excludeUserIds, true)) {
                continue;
            }

            if (static::isUserEligibleToReceive($user, $record, $permissions, $targetCompanyId, $targetBranchId)) {
                $qualified->push($user);
            }
        }

        return $qualified->unique('id')->values();
    }

    /**
     * Determine if a user satisfies all update permission and scoping criteria for a record.
     */
    public static function isUserEligibleToReceive(
        User $user,
        Model $record,
        array $permissions,
        ?int $companyId,
        ?int $branchId
    ): bool {
        // User must be active
        if ($user->status !== 'active') {
            return false;
        }

        if ($record instanceof CashHandover) {
            if (! $record->isSubmitted()) {
                return false;
            }

            try {
                CashHandoverService::authorizeHandover($record, 'confirm_cash_handover', $user);

                return true;
            } catch (AuthorizationException) {
                return false;
            }
        }

        // 1. Super Admin: Global update rights across all companies
        if ($user->isSuperAdmin()) {
            return true;
        }

        // 2. Owner: Must own the specific target company
        if ($user->isOwner()) {
            if ($companyId === null) {
                return true;
            }

            $ownedCompanyIds = array_map('intval', $user->getOwnedCompanyIds());

            return in_array((int) $companyId, $ownedCompanyIds, true);
        }

        $effectivePermissions = ! empty($permissions) ? $permissions : static::resolveDefaultUpdatePermissions($record);
        if ($effectivePermissions === [] || ! $user->hasAnyPermission($effectivePermissions)
            || ! Gate::forUser($user)->allows('update', $record)) {
            return false;
        }
        // 4. Company Scope verification
        if ($companyId !== null) {
            $authorizedCompanyIds = array_map('intval', CompanyFormService::getAuthorizedCompanyIds($user));
            if (! in_array((int) $companyId, $authorizedCompanyIds, true)) {
                return false;
            }
        }

        // 5. Branch Scope verification
        if ($branchId !== null) {
            if (method_exists($user, 'canAccessAllBranches') && ! $user->canAccessAllBranches() && $user->branch_id) {
                if ((int) $user->branch_id !== (int) $branchId) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Resolve default update permissions by model type.
     */
    public static function resolveDefaultUpdatePermissions(Model $record): array
    {
        return match (true) {
            $record instanceof CashHandover => ['confirm_cash_handover'],
            $record instanceof ForeignEmployee => ['update_foreign_employee'],
            $record instanceof SaudiEmployee => ['update_saudi_employee'],
            $record instanceof Employee => ($record->user?->user_type === UserType::FOREIGN_EMPLOYEE)
                ? ['update_foreign_employee']
                : ['update_saudi_employee'],
            $record instanceof Company => ['update_company'],
            $record instanceof Leave => ['update_leave'],
            $record instanceof Installment => ['update_installment'],
            $record instanceof InstallmentPayment => ['update_installment_payment'],
            $record instanceof Debt => ['update_debt'],
            $record instanceof Violation => ['update_violation'],
            $record instanceof Task => ['update_task'],
            $record instanceof TaskAssignment => ['update_task_assignment'],
            $record instanceof Invoice => ['update_invoice'],
            $record instanceof Ticket => ['update_ticket'],
            $record instanceof ServiceTransaction => ['update_service_transaction'],
            default => [],
        };
    }

    /**
     * Extract company_id from a record across supported relationship patterns.
     */
    public static function resolveCompanyId(Model $record): ?int
    {
        if ($record instanceof Company) {
            return (int) $record->id;
        }

        if (isset($record->company_id) && $record->company_id !== null) {
            return (int) $record->company_id;
        }

        if (method_exists($record, 'employee') && $record->employee) {
            return $record->employee->company_id ? (int) $record->employee->company_id : null;
        }

        if (method_exists($record, 'company') && $record->company) {
            return $record->company->id ? (int) $record->company->id : null;
        }

        if (method_exists($record, 'installment') && $record->installment?->employee) {
            return $record->installment->employee->company_id ? (int) $record->installment->employee->company_id : null;
        }

        return null;
    }

    /**
     * Extract branch_id from a record across supported relationship patterns.
     */
    public static function resolveBranchId(Model $record): ?int
    {
        if (isset($record->branch_id) && $record->branch_id !== null) {
            return (int) $record->branch_id;
        }

        if (method_exists($record, 'employee') && $record->employee) {
            return $record->employee->branch_id ? (int) $record->employee->branch_id : null;
        }

        return null;
    }
}
