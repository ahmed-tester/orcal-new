<?php

namespace App\Services;

use App\Enums\GeneralStatus;
use App\Enums\RewardStatus;
use App\Models\Employee;
use App\Models\EmployeeReward;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RewardSummaryService
{
    /**
     * Build the aggregated summary query where each row is an Employee
     * with reward metrics computed over the selected year.
     */
    public static function summaryQuery(array $filters = []): Builder
    {
        $year = self::resolveYear($filters['year'] ?? null);

        $aggregate = EmployeeReward::query()
            ->selectRaw('
                employee_id,
                COUNT(*) as total_rewards,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled_count,
                SUM(CASE WHEN status != ? THEN COALESCE(net_amount, 0) ELSE 0 END) as total_amount,
                SUM(CASE WHEN status = ? THEN COALESCE(net_amount, 0) ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status = ? THEN COALESCE(net_amount, 0) ELSE 0 END) as pending_amount,
                SUM(CASE WHEN status != ? THEN COALESCE(hours_count, 0) ELSE 0 END) as total_hours,
                SUM(CASE WHEN status != ? THEN COALESCE(minutes_count, 0) ELSE 0 END) as total_minutes,
                MAX(reward_date) as last_reward_date
            ', [
                RewardStatus::PENDING->value,
                RewardStatus::APPROVED->value,
                RewardStatus::PAID->value,
                RewardStatus::CANCELLED->value,
                RewardStatus::CANCELLED->value,
                RewardStatus::PAID->value,
                RewardStatus::PENDING->value,
                RewardStatus::CANCELLED->value,
                RewardStatus::CANCELLED->value,
            ])
            ->when($year !== null, fn ($q) => $q->whereYear('reward_date', $year))
            ->groupBy('employee_id');

        $query = Employee::query()
            ->leftJoinSub($aggregate, 'rw', fn ($join) => $join->on('rw.employee_id', '=', 'employees.id'))
            ->select(['employees.*'])
            ->selectRaw('
                COALESCE(rw.total_rewards, 0) as total_rewards,
                COALESCE(rw.pending_count, 0) as pending_count,
                COALESCE(rw.approved_count, 0) as approved_count,
                COALESCE(rw.paid_count, 0) as paid_count,
                COALESCE(rw.cancelled_count, 0) as cancelled_count,
                COALESCE(rw.total_amount, 0) as total_amount,
                COALESCE(rw.paid_amount, 0) as paid_amount,
                COALESCE(rw.pending_amount, 0) as pending_amount,
                COALESCE(rw.total_hours, 0) as total_hours,
                COALESCE(rw.total_minutes, 0) as total_minutes,
                rw.last_reward_date as last_reward_date
            ')
            ->with(['user', 'details', 'branch', 'jobTitle', 'company']);

        // Only show employees who have at least one reward
        $query->whereNotNull('rw.total_rewards');

        self::applyAccessScope($query);
        self::applyFilters($query, $filters);

        return $query;
    }

    /**
     * Compute KPI stats over every employee within the current filters.
     */
    public static function getStats(array $filters = []): array
    {
        $rows = self::summaryQuery($filters)->get();

        return [
            'employees_count' => $rows->count(),
            'total_rewards' => (int) $rows->sum('total_rewards'),
            'pending_count' => (int) $rows->sum('pending_count'),
            'approved_count' => (int) $rows->sum('approved_count'),
            'paid_count' => (int) $rows->sum('paid_count'),
            'total_amount' => round((float) $rows->sum('total_amount'), 2),
            'paid_amount' => round((float) $rows->sum('paid_amount'), 2),
            'pending_amount' => round((float) $rows->sum('pending_amount'), 2),
        ];
    }

    /**
     * Comprehensive reward details for a specific employee.
     */
    public static function getEmployeeRewardDetails(int $employeeId, ?int $year = null): ?array
    {
        $employee = Employee::with([
            'user',
            'company',
            'branch',
            'jobTitle',
            'department',
            'details',
        ])->find($employeeId);

        if (! $employee) {
            return null;
        }

        if (! self::canAccessEmployee($employee)) {
            return null;
        }

        $year = self::resolveYear($year);

        $rewards = EmployeeReward::with(['creator', 'treasuryTransaction'])
            ->where('employee_id', $employeeId)
            ->when($year !== null, fn ($q) => $q->whereYear('reward_date', $year))
            ->orderBy('reward_date', 'desc')
            ->get();

        $activeRewards = $rewards->filter(fn ($r) => $r->status !== RewardStatus::CANCELLED);

        return [
            'employee' => $employee,
            'year' => $year,
            'rewards' => $rewards,
            'stats' => [
                'total_rewards' => $rewards->count(),
                'total_amount' => round((float) $activeRewards->sum('net_amount'), 2),
                'paid_amount' => round((float) $activeRewards->where('status', RewardStatus::PAID)->sum('net_amount'), 2),
                'pending_count' => $activeRewards->where('status', RewardStatus::PENDING)->count(),
                'approved_count' => $activeRewards->where('status', RewardStatus::APPROVED)->count(),
                'paid_count' => $activeRewards->where('status', RewardStatus::PAID)->count(),
                'total_hours' => round((float) $activeRewards->sum('hours_count'), 2),
                'total_minutes' => (int) $activeRewards->sum('minutes_count'),
            ],
        ];
    }

    /**
     * Unpaginated summary rows for CSV export.
     */
    public static function exportRows(array $filters = []): Collection
    {
        return self::summaryQuery($filters)
            ->orderBy('total_amount', 'desc')
            ->orderBy('employees.id')
            ->get();
    }

    /**
     * Options for the year filter.
     */
    public static function yearOptions(int $yearsBack = 5): array
    {
        $options = [];
        $currentYear = now()->year;

        for ($i = 0; $i < $yearsBack; $i++) {
            $year = $currentYear - $i;
            $options[$year] = (string) $year;
        }

        return $options;
    }

    public static function resolveYear(mixed $year): ?int
    {
        if ($year === null || $year === '' || $year === 'all') {
            return null;
        }

        return (int) $year;
    }

    /**
     * Company and branch permission scoping directly on employees query.
     */
    public static function applyAccessScope(Builder $query): Builder
    {
        return CompanyAccessScopeService::apply($query);
    }

    /**
     * Verify whether the authenticated user has access to a specific employee.
     */
    public static function canAccessEmployee(Employee $employee): bool
    {
        return self::applyAccessScope(Employee::query())->whereKey($employee->getKey())->exists();
    }

    /**
     * Apply filter criteria to the employees query.
     */
    protected static function applyFilters(Builder $query, array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $group) use ($search) {
                $group->where('employees.employee_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('details', fn ($dq) => $dq->where('national_id', 'like', "%{$search}%")
                        ->orWhere('iqama_number', 'like', "%{$search}%")
                    );
            });
        }

        if (! empty($filters['company_id'])) {
            $companyIds = (array) $filters['company_id'];
            $companyIds = array_filter($companyIds, fn ($id) => $id !== null && $id !== '' && $id !== 'all');
            if (! empty($companyIds)) {
                $query->whereIn('employees.company_id', $companyIds);
            }
        }

        if (! empty($filters['branch_id'])) {
            $branchIds = (array) $filters['branch_id'];
            $branchIds = array_filter($branchIds, fn ($id) => $id !== null && $id !== '' && $id !== 'all');
            if (! empty($branchIds)) {
                $query->whereIn('employees.branch_id', $branchIds);
            }
        }

        $employmentStatus = $filters['employment_status'] ?? 'all';
        if ($employmentStatus === 'active') {
            $query->where('employees.status', GeneralStatus::ACTIVE);
        } elseif ($employmentStatus === 'inactive') {
            $query->where('employees.status', '!=', GeneralStatus::ACTIVE);
        }

        $rewardFilter = $filters['reward_filter'] ?? 'all';
        if ($rewardFilter === 'has_pending') {
            $query->where('rw.pending_count', '>', 0);
        } elseif ($rewardFilter === 'has_paid') {
            $query->where('rw.paid_count', '>', 0);
        }

        return $query;
    }
}

