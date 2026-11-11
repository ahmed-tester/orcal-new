<?php

namespace App\Services;

use App\Enums\GeneralStatus;
use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\Leave;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveSummaryService
{
    /**
     * Build the aggregated summary query where each row is an Employee
     * with metrics computed over the selected year and all-time balances.
     */
    public static function summaryQuery(array $filters = []): Builder
    {
        $year = self::resolveYear(array_key_exists('year', $filters) ? $filters['year'] : now()->year);
        $today = now()->toDateString();
        $annualTypeId = LeaveBalanceService::getDedicatedAnnualLeaveTypeId();
        $days = self::periodDaysSql($year);

        // 1. Period aggregation subquery (within selected year)
        $aggregate = Leave::query()
            ->selectRaw("
                employee_id,
                COUNT(*) as total_requests,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved_leaves_count,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending_leaves_count,
                SUM(CASE WHEN status = ? THEN {$days} ELSE 0 END) as total_approved_days,
                SUM(CASE WHEN status = ? AND leave_type_id = ? THEN {$days} ELSE 0 END) as annual_approved_days_period,
                SUM(CASE WHEN status = ? AND (leave_type_id != ? OR leave_type_id IS NULL) THEN {$days} ELSE 0 END) as other_approved_days_period,
                MAX(CASE WHEN status = ? THEN end_date ELSE NULL END) as last_leave_end_date
            ", [
                LeaveStatus::APPROVED->value,
                LeaveStatus::PENDING->value,
                LeaveStatus::APPROVED->value,
                LeaveStatus::APPROVED->value, $annualTypeId,
                LeaveStatus::APPROVED->value, $annualTypeId,
                LeaveStatus::APPROVED->value,
            ])
            ->when($year !== null, fn ($q) => $q->whereDate('start_date', '<=', sprintf('%04d-12-31', $year))
                ->whereDate('end_date', '>=', sprintf('%04d-01-01', $year)))
            ->groupBy('employee_id');

        $active = Leave::query()
            ->selectRaw('employee_id, MAX(id) as active_leave_id')
            ->where('status', LeaveStatus::APPROVED)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->where(fn ($q) => $q->whereNull('actual_return_date')->orWhereDate('actual_return_date', '>', $today))
            ->groupBy('employee_id');

        // 2. All-time historical annual leave usage subquery (for accurate balance)
        $historicalAnnualAggregate = Leave::query()
            ->selectRaw('
                employee_id,
                SUM(CASE WHEN status = ? AND leave_type_id = ? THEN days ELSE 0 END) as historical_annual_used_days
            ', [LeaveStatus::APPROVED->value, $annualTypeId])
            ->groupBy('employee_id');

        // 3. Main Employee query
        $query = Employee::query()
            ->leftJoinSub($active, 'alv', fn ($join) => $join->on('alv.employee_id', '=', 'employees.id'))
            ->leftJoinSub($aggregate, 'lv', fn ($join) => $join->on('lv.employee_id', '=', 'employees.id'))
            ->leftJoinSub($historicalAnnualAggregate, 'hlv', fn ($join) => $join->on('hlv.employee_id', '=', 'employees.id'))
            ->select([
                'employees.*',
            ])
            ->selectRaw('
                COALESCE(lv.total_requests, 0) as total_requests,
                COALESCE(lv.approved_leaves_count, 0) as approved_leaves_count,
                COALESCE(lv.pending_leaves_count, 0) as pending_leaves_count,
                COALESCE(lv.total_approved_days, 0) as total_approved_days,
                COALESCE(lv.annual_approved_days_period, 0) as annual_approved_days_period,
                COALESCE(lv.other_approved_days_period, 0) as other_approved_days_period,
                CASE WHEN alv.active_leave_id IS NULL THEN 0 ELSE 1 END as is_on_leave_today,
                alv.active_leave_id as active_leave_id,
                lv.last_leave_end_date as last_leave_end_date,
                COALESCE(hlv.historical_annual_used_days, 0) as historical_annual_used_days
            ')
            ->with(['user', 'details', 'branch', 'jobTitle', 'company']);

        self::applyAccessScope($query);
        self::applyFilters($query, $filters);

        return $query;
    }

    public static function resolveYear(mixed $year): ?int
    {
        if ($year === null || $year === '' || $year === 'all') {
            return null;
        }

        validator(['year' => $year], ['year' => ['required', 'integer', 'between:1900,9999']])->validate();

        return (int) $year;
    }

    protected static function periodDaysSql(?int $year): string
    {
        if ($year === null) {
            return 'days';
        }

        $start = sprintf('%04d-01-01', $year);
        $end = sprintf('%04d-12-31', $year);

        return Leave::query()->getConnection()->getDriverName() === 'sqlite'
            ? "MAX(0, CAST(julianday(MIN(end_date, '$end')) - julianday(MAX(start_date, '$start')) + 1 AS INTEGER))"
            : "GREATEST(0, DATEDIFF(LEAST(end_date, '$end'), GREATEST(start_date, '$start')) + 1)";
    }

    public static function daysInYear(Leave $leave, ?int $year): int
    {
        if ($year === null) {
            return (int) $leave->days;
        }
        if (! $leave->start_date || ! $leave->end_date) {
            return 0;
        }

        $start = $leave->start_date->copy()->max(Carbon::createMidnightDate($year, 1, 1));
        $end = $leave->end_date->copy()->min(Carbon::createMidnightDate($year, 12, 31));

        return $start->gt($end) ? 0 : (int) $start->diffInDays($end) + 1;
    }

    public static function canChangeStatus(Leave $leave): bool
    {
        return auth()->user()?->can('change_leave_status', $leave)
            && $leave->employee && self::canAccessEmployee($leave->employee);
    }

    public static function changeStatus(int $leaveId, LeaveStatus $status, ?string $reason = null): void
    {
        DB::transaction(function () use ($leaveId, $status, $reason) {
            $leave = Leave::query()->lockForUpdate()->findOrFail($leaveId);
            $employee = Employee::query()->lockForUpdate()->findOrFail($leave->employee_id);
            $leave->setRelation('employee', $employee);
            if (! self::canChangeStatus($leave)) {
                throw new AuthorizationException;
            }
            if ($leave->status !== LeaveStatus::PENDING) {
                throw ValidationException::withMessages(['status' => __('leaves.notifications.invalid_status')]);
            }
            if ($status === LeaveStatus::REJECTED) {
                validator(['rejection_reason' => $reason], ['rejection_reason' => ['required', 'string', 'max:1000']])->validate();
            } elseif ($status === LeaveStatus::APPROVED) {
                $balance = LeaveBalanceService::calculateBalance($employee, $leave->leave_type_id, $leave->id);
                if ($leave->days > $balance['available_days'] && ! LeaveBalanceService::canUserOverrideLeaveBalance()) {
                    throw ValidationException::withMessages(['status' => __('leaves.notifications.insufficient_balance', [
                        'available' => $balance['available_days'], 'days' => $leave->days,
                    ])]);
                }
            } else {
                throw new \InvalidArgumentException('Unsupported leave decision.');
            }
            $leave->update([
                'status' => $status,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejection_reason' => $status === LeaveStatus::REJECTED ? trim($reason) : null,
            ]);
        });
    }

    /**
     * Compute comprehensive leave balance for an employee using service duration
     * matching LeaveBalanceService.
     *
     * @return array{
     *     annual_rate: int,
     *     hire_date: ?string,
     *     service_months: float,
     *     years_worked: float,
     *     accrued_days: int,
     *     used_days: float,
     *     available_days: int
     * }
     */
    public static function computeEmployeeBalance(Employee $employee, float $historicalUsedDays = 0): array
    {
        $hireDate = $employee->hire_date
            ? Carbon::parse($employee->hire_date)
            : ($employee->created_at ? Carbon::parse($employee->created_at) : now());

        $now = now();
        $targetDate = $now->lessThan($hireDate) ? $hireDate : $now;
        $daysWorked = max(0, $hireDate->diffInDays($targetDate));
        $yearsWorked = $daysWorked / 365.25;

        $annualRate = (int) ($employee->annual_leave_days ?? 21);
        if ($annualRate <= 0) {
            $annualRate = 21;
        }

        $accruedDays = (int) floor($yearsWorked * $annualRate);
        $availableDays = (int) max(0, floor($accruedDays - $historicalUsedDays));
        $serviceMonths = round($daysWorked / 30.4375, 1);

        return [
            'annual_rate' => $annualRate,
            'hire_date' => $hireDate->format('Y-m-d'),
            'service_months' => $serviceMonths,
            'years_worked' => round($yearsWorked, 1),
            'accrued_days' => $accruedDays,
            'used_days' => $historicalUsedDays,
            'available_days' => $availableDays,
        ];
    }

    /**
     * Compute KPI stats over every employee within the current filters.
     */
    public static function getStats(array $filters = []): array
    {
        $rows = self::summaryQuery($filters)->get();

        $totalAvailableAnnualDays = 0;
        foreach ($rows as $employee) {
            $balance = self::computeEmployeeBalance($employee, (float) ($employee->historical_annual_used_days ?? 0));
            $totalAvailableAnnualDays += $balance['available_days'];
        }

        return [
            'employees_count' => $rows->count(),
            'on_leave_today_count' => $rows->where('is_on_leave_today', 1)->count(),
            'pending_requests_count' => (int) $rows->sum('pending_leaves_count'),
            'total_approved_days' => (int) $rows->sum('total_approved_days'),
            'total_available_annual_days' => $totalAvailableAnnualDays,
        ];
    }

    /**
     * Comprehensive leave details and history for a specific employee.
     */
    public static function getEmployeeLeaveDetails(int $employeeId, ?int $year = null): ?array
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
        $annualTypeId = LeaveBalanceService::getDedicatedAnnualLeaveTypeId();

        // 1. Calculate overall balance
        $balance = LeaveBalanceService::calculateBalance($employee, $annualTypeId);

        // 2. Fetch all historical leaves for this employee
        $leaves = Leave::with(['leaveType', 'approver'])
            ->where('employee_id', $employeeId)
            ->orderBy('start_date', 'desc')
            ->get();

        // 3. Calculate year-specific stats
        $yearLeaves = $leaves->filter(fn (Leave $leave) => self::daysInYear($leave, $year) > 0);

        $yearApprovedDays = (int) $yearLeaves->where('status', LeaveStatus::APPROVED)
            ->sum(fn (Leave $leave) => self::daysInYear($leave, $year));
        $yearPendingCount = $yearLeaves->where('status', LeaveStatus::PENDING)->count();

        // 4. Check active leave today
        $today = now()->toDateString();
        $activeLeave = $leaves->first(function (Leave $l) use ($today) {
            if ($l->status !== LeaveStatus::APPROVED) {
                return false;
            }
            $start = $l->start_date ? Carbon::parse($l->start_date)->toDateString() : null;
            $end = $l->end_date ? Carbon::parse($l->end_date)->toDateString() : null;

            return $start && $end && $today >= $start && $today <= $end
                && (! $l->actual_return_date || $today < $l->actual_return_date->toDateString());
        });

        return [
            'employee' => $employee,
            'year' => $year,
            'balance' => $balance,
            'leaves' => $leaves,
            'active_leave' => $activeLeave,
            'year_stats' => [
                'total_leaves_count' => $leaves->count(),
                'year_leaves_count' => $yearLeaves->count(),
                'year_approved_days' => $yearApprovedDays,
                'year_pending_count' => $yearPendingCount,
            ],
        ];
    }

    /**
     * Unpaginated summary rows for CSV export.
     */
    public static function exportRows(array $filters = []): Collection
    {
        return self::summaryQuery($filters)
            ->orderBy('is_on_leave_today', 'desc')
            ->orderBy('pending_leaves_count', 'desc')
            ->orderBy('employees.id')
            ->get();
    }

    /**
     * Options for the year filter (e.g. current year and past 4 years).
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

        $leaveFilter = $filters['leave_filter'] ?? 'all';
        if ($leaveFilter === 'on_leave') {
            $query->whereNotNull('alv.active_leave_id');
        } elseif ($leaveFilter === 'has_pending') {
            $query->where('lv.pending_leaves_count', '>', 0);
        }

        return $query;
    }
}
