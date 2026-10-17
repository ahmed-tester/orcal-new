<?php

namespace App\Services\Reports\Types;

use App\Enums\UserType;
use App\Services\Reports\Contracts\ReportInterface;
use Carbon\Carbon;

abstract class AbstractReport implements ReportInterface
{
    /**
     * Get the currently authenticated user.
     */
    protected function getAuthUser(): ?\App\Models\User
    {
        return auth()->user();
    }

    /**
     * Get company IDs the current user is authorized to see.
     * - Super Admin: null (no restriction)
     * - Owner: their company IDs
     * - Employee: their company ID
     */
    protected function getAuthCompanyIds(): ?array
    {
        $user = $this->getAuthUser();
        if (!$user) return [];

        // Super Admin sees everything
        if ($user->user_type === UserType::SUPER_ADMIN) {
            return null;
        }

        // Owner sees their companies
        if ($user->user_type === UserType::OWNER) {
            return $user->owners()->pluck('company_id')->toArray();
        }

        // Employee/Supervisor/Admin sees their company only
        $companyId = $user->employee?->company_id;
        return $companyId ? [$companyId] : [];
    }

    /**
     * Get the branch ID the current user is restricted to.
     * - Super Admin / Owner: null (no restriction, or filtered by UI)
     * - Employee: their branch_id
     */
    protected function getAuthBranchId(): ?int
    {
        $user = $this->getAuthUser();
        if (!$user) return null;

        // Super Admin and Owner are not restricted to a branch
        if (in_array($user->user_type, [UserType::SUPER_ADMIN, UserType::OWNER])) {
            return null;
        }

        // Employees are restricted to their branch
        return $user->employee?->branch_id;
    }

    /**
     * Apply company-level scoping to a query.
     * For models that have a direct 'company_id' column.
     */
    protected function applyCompanyScope($query, string $companyColumn = 'company_id')
    {
        $companyIds = $this->getAuthCompanyIds();

        if ($companyIds === null) {
            return $query; // Super Admin — no restriction
        }

        return $query->whereIn($companyColumn, $companyIds);
    }

    /**
     * Apply company-level scoping via a relation (e.g., 'employee.company_id').
     */
    protected function applyCompanyScopeViaRelation($query, string $relation, string $companyColumn = 'company_id')
    {
        $companyIds = $this->getAuthCompanyIds();

        if ($companyIds === null) {
            return $query; // Super Admin
        }

        return $query->whereHas($relation, fn($q) => $q->whereIn($companyColumn, $companyIds));
    }

    /**
     * Apply branch-level scoping for employees.
     * Uses auth branch if no branchId is provided from UI filter.
     * For models with a direct 'branch_id' column.
     */
    protected function applyBranchScope($query, ?int $filterBranchId, string $branchColumn = 'branch_id')
    {
        $authBranchId = $this->getAuthBranchId();

        // Employee is restricted — always use their branch, ignore UI filter
        if ($authBranchId) {
            return $query->where($branchColumn, $authBranchId);
        }

        // Owner / Super Admin — use UI filter if provided
        return $query->when($filterBranchId, fn($q) => $q->where($branchColumn, $filterBranchId));
    }

    /**
     * Apply branch-level scoping via a relation (e.g., 'employee.branch_id').
     */
    protected function applyBranchScopeViaRelation($query, ?int $filterBranchId, string $relation = 'employee', string $branchColumn = 'branch_id')
    {
        $authBranchId = $this->getAuthBranchId();

        if ($authBranchId) {
            return $query->whereHas($relation, fn($q) => $q->where($branchColumn, $authBranchId));
        }

        return $query->when($filterBranchId, fn($q) => $q->whereHas($relation, fn($eq) => $eq->where($branchColumn, $filterBranchId)));
    }

    protected function parseDateFrom(?string $date): ?Carbon
    {
        return $date ? Carbon::parse($date)->startOfDay() : null;
    }

    protected function parseDateTo(?string $date): ?Carbon
    {
        return $date ? Carbon::parse($date)->endOfDay() : null;
    }

    /**
     * Apply date range filter to query.
     */
    protected function applyDateFilter($query, ?string $dateFrom, ?string $dateTo, string $dateColumn = 'created_at')
    {
        $dateFromCarbon = $this->parseDateFrom($dateFrom);
        $dateToCarbon = $this->parseDateTo($dateTo);

        return $query
            ->when($dateFrom, fn($q) => $q->whereDate($dateColumn, '>=', $dateFromCarbon))
            ->when($dateTo, fn($q) => $q->whereDate($dateColumn, '<=', $dateToCarbon));
    }

    /**
     * Apply status filter to query.
     */
    protected function applyStatusFilter($query, string $status, string $column = 'status')
    {
        return $query->when($status && $status !== 'all', fn($q) => $q->where($column, $status));
    }

    /**
     * Apply branch filter via a relation (e.g. employee.branch_id).
     */
    protected function applyBranchFilter($query, ?int $branchId, string $relation = 'employee')
    {
        return $query->when($branchId, fn($q) => $q->whereHas($relation, fn($eq) => $eq->where('branch_id', $branchId)));
    }

    /**
     * Apply search filter with proper sub-query grouping.
     */
    protected function applySearchFilter($query, ?string $search, \Closure $searchCallback)
    {
        return $query->when($search, function ($q) use ($search, $searchCallback) {
            $q->where(function ($sub) use ($search, $searchCallback) {
                $searchCallback($sub, $search);
            });
        });
    }

    /**
     * Default stats implementation — override in subclasses that need stats.
     */
    public function getStats(?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null): array
    {
        return [];
    }

    /**
     * Translate a status value using existing Enums.
     * Supports BackedEnum instances directly, or raw string values.
     */
    protected function translateStatus(mixed $status): string
    {
        // If already a BackedEnum with getLabel(), use it directly
        if ($status instanceof \BackedEnum && method_exists($status, 'getLabel')) {
            return $status->getLabel();
        }

        // If it's a string, try to resolve it from known enums
        if (is_string($status)) {
            $enumClasses = [
                \App\Enums\GeneralStatus::class,
                \App\Enums\AttendanceStatus::class,
                \App\Enums\LeaveStatus::class,
                \App\Enums\PaymentStatus::class,
                \App\Enums\InstallmentStatus::class,
                \App\Enums\RewardStatus::class,
                \App\Enums\OfferStatus::class,
                \App\Enums\PosDeviceStatus::class,
                \App\Enums\TreasuryTransactionType::class,
                \App\Enums\OfferType::class,
                \App\Enums\PricingType::class,
                \App\Enums\InstallmentPaymentSource::class,
            ];

            foreach ($enumClasses as $enumClass) {
                $resolved = $enumClass::tryFrom($status);
                if ($resolved) {
                    return $resolved->getLabel();
                }
            }

            if (\Illuminate\Support\Facades\Lang::has('reports.status.' . $status)) {
                return __('reports.status.' . $status);
            }
        }

        return $status ?? '—';
    }
}
