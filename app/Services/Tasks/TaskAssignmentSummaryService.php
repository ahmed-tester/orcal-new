<?php

namespace App\Services\Tasks;

use App\Enums\TaskStatus;
use App\Models\Employee;
use App\Models\TaskAssignment;
use App\Models\TaskAssignmentItem;
use App\Models\User;
use App\Services\CompanyFormService;
use App\Services\EmployeePerformanceAccessService;
use App\Services\FilterBarService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TaskAssignmentSummaryService
{
    /**
     * Aggregate columns produced by the grouped item query.
     *
     * @var array<string>
     */
    public const TOTALS = [
        'total_items',
        'completed_count',
        'in_progress_count',
        'pending_count',
        'pending_review_count',
        'returned_count',
        'postponed_count',
        'overdue_count',
        'completed_in_period',
        'due_in_period',
        'due_completed_count',
        'on_time_count',
        'dated_completed_count',
        'approved_items_count',
        'assigned_weight_sum',
        'weight_sum',
        'score_sum',
    ];

    /**
     * Assignments visible to the authenticated user (company/branch scoped
     * through the employee relation). Task assignments are limited to the same
     * Saudi employee definition used by TaskAssignmentForm (saudiOnly: true).
     */
    public static function assignments(array $filters = []): Builder
    {
        $companyIds = array_filter((array) FilterBarService::values($filters['company_id'] ?? null));
        $employeeFilters = Arr::except($filters, ['company_id']);

        $query = TaskAssignment::query()
            ->whereIn('employee_id', self::filteredEmployees($employeeFilters)->select('employees.id'));

        $user = auth()->user();
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (! $user->isSuperAdmin()) {
            $authorizedCompanyIds = CompanyFormService::getAuthorizedCompanyIds($user);
            if ($authorizedCompanyIds === []) {
                return $query->whereRaw('1 = 0');
            }

            $query->where(function (Builder $cq) use ($authorizedCompanyIds, $user): void {
                if ($user->employee) {
                    $cq->orWhere('task_assignments.employee_id', $user->employee->id);
                }

                $cq->orWhereHas('employee', fn (Builder $e) => $e->whereIn('employees.company_id', $authorizedCompanyIds))
                    ->orWhereHas('items', fn (Builder $i) => $i->whereIn('task_assignment_items.company_id', $authorizedCompanyIds));
            });
        }

        if ($companyIds !== []) {
            $query->where(function (Builder $cq) use ($companyIds): void {
                $cq->whereHas('employee', fn (Builder $e) => $e->whereIn('employees.company_id', $companyIds))
                    ->orWhereHas('items', fn (Builder $i) => $i->whereIn('task_assignment_items.company_id', $companyIds));
            });
        }

        if (EmployeePerformanceAccessService::isSupervisor($user)) {
            $windows = self::teamWindows($user, $filters);

            $query->where(function (Builder $team) use ($user, $windows) {
                $team->where('task_assignments.employee_id', $user->employee?->id ?? 0);

                foreach ($windows as $window) {
                    $team->orWhere(function (Builder $assignment) use ($window) {
                        $assignment->whereIn('task_assignments.employee_id', $window['employees'])
                            ->whereBetween('task_assignments.assignment_date', [$window['start'], $window['end']])
                            ->whereHas('employee', fn (Builder $employee) => $employee->where('employees.company_id', $window['company']));
                    });
                }
            });
        } elseif (EmployeePerformanceAccessService::isFormerOrRevokedSupervisor($user)) {
            $query->where('task_assignments.employee_id', $user->employee?->id ?? 0);
        }

        return $query;
    }

    /**
     * Task assignment items limited to the authorized assignments and the
     * active period filters.
     */
    public static function items(array $filters = []): Builder
    {
        $query = TaskAssignmentItem::query()
            ->whereIn('task_assignment_id', self::assignments($filters)->select('task_assignments.id'));

        $user = auth()->user();
        if ($user && ! $user->isSuperAdmin()) {
            $authorizedCompanyIds = CompanyFormService::getAuthorizedCompanyIds($user);
            if ($authorizedCompanyIds === []) {
                return $query->whereRaw('1 = 0');
            }

            $query->where(function (Builder $cq) use ($authorizedCompanyIds, $user): void {
                if ($user->employee) {
                    $cq->orWhereHas('taskAssignment', fn (Builder $a) => $a->where('task_assignments.employee_id', $user->employee->id));
                }

                $cq->orWhereIn('task_assignment_items.company_id', $authorizedCompanyIds)
                    ->orWhere(function (Builder $fallback) use ($authorizedCompanyIds): void {
                        $fallback->whereNull('task_assignment_items.company_id')
                            ->whereHas('taskAssignment.employee', fn (Builder $e) => $e->whereIn('employees.company_id', $authorizedCompanyIds));
                    });
            });
        }

        $companyIds = array_filter((array) FilterBarService::values($filters['company_id'] ?? null));
        if ($companyIds !== []) {
            $query->where(function (Builder $cq) use ($companyIds): void {
                $cq->whereIn('task_assignment_items.company_id', $companyIds)
                    ->orWhere(function (Builder $fallback) use ($companyIds): void {
                        $fallback->whereNull('task_assignment_items.company_id')
                            ->whereHas('taskAssignment.employee', fn (Builder $e) => $e->whereIn('employees.company_id', $companyIds));
                    });
            });
        }

        self::applyItemFilters($query, $filters);

        return $query;
    }

    /**
     * Employees available to the authenticated user, used for summary rows.
     * The task assignment phase only covers Saudi employees.
     */
    public static function employeesQuery(array $filters = []): Builder
    {
        $query = self::filteredEmployees($filters);
        $user = auth()->user();

        if ($user && EmployeePerformanceAccessService::isSupervisor($user)) {
            $windows = self::teamWindows($user, $filters);

            $query->where(function (Builder $team) use ($user, $windows) {
                $team->whereKey($user->employee?->id ?? 0);

                foreach ($windows as $window) {
                    $team->orWhere(fn (Builder $employee) => $employee
                        ->where('employees.company_id', $window['company'])
                        ->whereIn('employees.id', $window['employees']));
                }
            });
        } elseif ($user && EmployeePerformanceAccessService::isFormerOrRevokedSupervisor($user)) {
            $query->whereKey($user->employee?->id ?? 0);
        }

        return $query;
    }

    private static function filteredEmployees(array $filters): Builder
    {
        $query = Employee::query()->nonTemporary()->saudi();
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (! $user->isSuperAdmin()) {
            $authorizedCompanyIds = CompanyFormService::getAuthorizedCompanyIds($user);
            if ($authorizedCompanyIds === []) {
                return $query->whereRaw('1 = 0');
            }

            if ($user->branch_id && ! $user->canAccessAllBranches() && ! $user->isOwner()) {
                $query->where('employees.branch_id', (int) $user->branch_id);
            }

            $query->where(function (Builder $cq) use ($authorizedCompanyIds): void {
                $cq->whereIn('employees.company_id', $authorizedCompanyIds)
                    ->orWhereExists(function ($sub) use ($authorizedCompanyIds): void {
                        $sub->selectRaw('1')
                            ->from('task_assignment_items')
                            ->join('task_assignments', 'task_assignments.id', '=', 'task_assignment_items.task_assignment_id')
                            ->whereColumn('task_assignments.employee_id', 'employees.id')
                            ->whereIn('task_assignment_items.company_id', $authorizedCompanyIds);
                    });
            });
        }

        self::applyEmployeeFilters($query, $filters);

        return $query;
    }

    private static function teamWindows(User $user, array $filters): array
    {
        [$start, $end] = self::resolveDateRange($filters);

        return EmployeePerformanceAccessService::teamWindows($user, Carbon::parse($start), Carbon::parse($end));
    }

    /**
     * One row per employee with task metrics, including employees without
     * assignments during the period.
     */
    public static function summaryQuery(array $filters = []): Builder
    {
        $query = self::employeesQuery($filters)
            ->leftJoinSub(self::grouped($filters), 'task_summary', fn ($join) => $join->on('task_summary.employee_id', '=', 'employees.id'))
            ->select('employees.*')
            ->selectRaw('
                COALESCE(task_summary.total_items, 0) as total_items,
                COALESCE(task_summary.completed_count, 0) as completed_count,
                COALESCE(task_summary.in_progress_count, 0) as in_progress_count,
                COALESCE(task_summary.pending_count, 0) as pending_count,
                COALESCE(task_summary.pending_review_count, 0) as pending_review_count,
                COALESCE(task_summary.returned_count, 0) as returned_count,
                COALESCE(task_summary.postponed_count, 0) as postponed_count,
                COALESCE(task_summary.overdue_count, 0) as overdue_count,
                COALESCE(task_summary.completed_in_period, 0) as completed_in_period,
                COALESCE(task_summary.due_in_period, 0) as due_in_period,
                COALESCE(task_summary.due_completed_count, 0) as due_completed_count,
                COALESCE(task_summary.on_time_count, 0) as on_time_count,
                COALESCE(task_summary.dated_completed_count, 0) as dated_completed_count,
                COALESCE(task_summary.approved_items_count, 0) as approved_items_count,
                COALESCE(task_summary.assigned_weight_sum, 0) as assigned_weight_sum,
                COALESCE(task_summary.weight_sum, 0) as weight_sum,
                COALESCE(task_summary.score_sum, 0) as score_sum,
                CASE WHEN COALESCE(task_summary.weight_sum, 0) > 0
                    THEN ROUND(((task_summary.score_sum * 1.0) / task_summary.weight_sum) * 100, 2)
                    ELSE 0 END as weighted_percentage
            ')
            ->with(['user', 'company', 'branch', 'department', 'jobTitle']);

        $quickColumn = match ($filters['quick_filter'] ?? null) {
            'total_items' => 'task_summary.total_items',
            'completed_in_period' => 'task_summary.completed_in_period',
            'overdue_now' => 'task_summary.overdue_count',
            'postponed' => 'task_summary.postponed_count',
            default => null,
        };

        if ($quickColumn !== null) {
            $query->whereRaw("COALESCE({$quickColumn}, 0) > 0");
        } elseif (! empty($filters['only_with_items'])) {
            $query->whereRaw('COALESCE(task_summary.total_items, 0) > 0');
        }

        return $query;
    }

    /**
     * Aggregate KPI values over every employee within the current filters.
     */
    public static function stats(array $filters = []): object
    {
        $row = DB::query()
            ->fromSub(self::grouped($filters), 'summary')
            ->selectRaw('COUNT(*) AS active_employees_count')
            ->selectRaw(implode(', ', array_map(
                fn (string $key) => "COALESCE(SUM({$key}), 0) AS {$key}",
                self::TOTALS
            )))
            ->first();

        $row->employees_count = ! empty($filters['only_with_items'])
            ? (int) ($row->active_employees_count ?? 0)
            : self::employeesQuery($filters)->count();

        return $row;
    }

    /**
     * The summary row of a single employee using exactly the same aggregate
     * query as the summary page.
     */
    public static function employeeRow(int $employeeId, array $filters = []): ?Employee
    {
        return self::summaryQuery($filters)->whereKey($employeeId)->first();
    }

    /**
     * Filtered task items of a single employee, ordered by due date.
     *
     * @return Collection<int, TaskAssignmentItem>
     */
    public static function employeeItems(int $employeeId, array $filters = []): Collection
    {
        return self::items($filters)
            ->whereHas('taskAssignment', fn (Builder $query) => $query->where('employee_id', $employeeId))
            ->with(['task', 'taskAssignment', 'reviews.submitter', 'reviews.decider'])
            ->orderByRaw('COALESCE(due_date, completed_at) IS NULL')
            ->orderByRaw('COALESCE(due_date, completed_at)')
            ->orderBy('id')
            ->get();
    }

    /**
     * Employee details and items for the statement view. Returns null with a
     * 404 when the employee is outside the authorized scope.
     *
     * @return array{employee: Employee, items: Collection<int, TaskAssignmentItem>}
     */
    public static function employeeDetails(int $employeeId, array $filters = []): array
    {
        $employee = self::employeeRow($employeeId, $filters);

        abort_if($employee === null, 404);

        return [
            'employee' => $employee,
            'items' => self::employeeItems($employeeId, $filters),
        ];
    }

    /**
     * Verify a specific employee is inside the authorized scope.
     */
    public static function canAccessEmployee(Employee $employee): bool
    {
        return self::employeesQuery()->whereKey($employee->getKey())->exists();
    }

    /**
     * Weighted task score from aggregated metrics.
     */
    public static function weightedPercentage(object $row): float
    {
        return round((float) ($row->weighted_percentage ?? 0), 2);
    }

    /**
     * Share of items due in the period that were completed by the period end.
     * Items completed inside the period but due outside it do not inflate the
     * numerator. Null when there are no items due in the period.
     */
    public static function completionRate(object $row): ?float
    {
        $due = (int) ($row->due_in_period ?? 0);

        return $due > 0
            ? round(((int) ($row->due_completed_count ?? 0) / $due) * 100, 2)
            : null;
    }

    /**
     * Completed items delivered on or before their due date. Null when no
     * completed item has both dates.
     */
    public static function onTimeRate(object $row): ?float
    {
        $dated = (int) ($row->dated_completed_count ?? 0);

        return $dated > 0
            ? round(((int) ($row->on_time_count ?? 0) / $dated) * 100, 2)
            : null;
    }

    /**
     * Extract the active date range from the filters. Defaults to the
     * current month so every entry point starts with the same period.
     *
     * @return array{0: string, 1: string}
     */
    public static function resolveDateRange(array $filters): array
    {
        $month = $filters['month'] ?? $filters['period'] ?? null;

        if (filled($month)) {
            $monthStart = self::monthStart((string) $month);

            return [
                $monthStart->format('Y-m-d'),
                $monthStart->copy()->endOfMonth()->format('Y-m-d'),
            ];
        }

        $from = filled($filters['date_from'] ?? null)
            ? Carbon::parse($filters['date_from'])->toDateString()
            : null;

        $to = filled($filters['date_to'] ?? null)
            ? Carbon::parse($filters['date_to'])->toDateString()
            : null;

        $now = now();

        return [
            $from ?? $now->copy()->startOfMonth()->toDateString(),
            $to ?? $now->copy()->endOfMonth()->toDateString(),
        ];
    }

    public static function monthStart(?string $month): Carbon
    {
        if (! $month || ! preg_match('/^(?!0000)\d{4}-(0[1-9]|1[0-2])$/D', $month)) {
            return now()->startOfMonth();
        }

        return Carbon::createFromFormat('!Y-m', $month);
    }

    /**
     * @return array<string, string>
     */
    public static function monthOptions(int $monthsBack = 18): array
    {
        $options = [];
        $cursor = now()->startOfMonth();

        for ($i = 0; $i < $monthsBack; $i++) {
            $options[$cursor->format('Y-m')] = $cursor->copy()->locale(app()->getLocale())->translatedFormat('F Y');
            $cursor->subMonth();
        }

        return $options;
    }

    /**
     * Grouped item aggregates per employee.
     */
    private static function grouped(array $filters): Builder
    {
        [$startDate, $endDate] = self::resolveDateRange($filters);

        return self::items($filters)
            ->join('task_assignments', 'task_assignments.id', '=', 'task_assignment_items.task_assignment_id')
            ->selectRaw('task_assignments.employee_id as employee_id')
            ->selectRaw('COUNT(*) as total_items')
            ->selectRaw('SUM(CASE WHEN task_assignment_items.status = ? THEN 1 ELSE 0 END) as completed_count', [TaskStatus::COMPLETED->value])
            ->selectRaw('SUM(CASE WHEN task_assignment_items.status = ? THEN 1 ELSE 0 END) as in_progress_count', [TaskStatus::IN_PROGRESS->value])
            ->selectRaw('SUM(CASE WHEN task_assignment_items.status = ? THEN 1 ELSE 0 END) as pending_count', [TaskStatus::PENDING->value])
            ->selectRaw('SUM(CASE WHEN task_assignment_items.status = ? THEN 1 ELSE 0 END) as pending_review_count', [TaskStatus::PENDING_REVIEW->value])
            ->selectRaw('SUM(CASE WHEN task_assignment_items.status = ? THEN 1 ELSE 0 END) as returned_count', [TaskStatus::RETURNED_FOR_REVISION->value])
            ->selectRaw('SUM(CASE WHEN task_assignment_items.status = ? THEN 1 ELSE 0 END) as postponed_count', [TaskStatus::POSTPONED->value])
            ->selectRaw(
                'SUM(CASE WHEN task_assignment_items.status IN (?, ?, ?) AND task_assignment_items.due_date IS NOT NULL AND task_assignment_items.due_date < ? THEN 1 ELSE 0 END) as overdue_count',
                [TaskStatus::PENDING->value, TaskStatus::IN_PROGRESS->value, TaskStatus::RETURNED_FOR_REVISION->value, today()->toDateString()]
            )
            ->selectRaw(
                'SUM(CASE WHEN task_assignment_items.status = ? AND task_assignment_items.completed_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as completed_in_period',
                [TaskStatus::COMPLETED->value, $startDate, $endDate]
            )
            ->selectRaw(
                'SUM(CASE WHEN task_assignment_items.due_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as due_in_period',
                [$startDate, $endDate]
            )
            ->selectRaw(
                'SUM(CASE WHEN task_assignment_items.due_date BETWEEN ? AND ? AND task_assignment_items.status = ? AND task_assignment_items.completed_at IS NOT NULL AND task_assignment_items.completed_at <= ? THEN 1 ELSE 0 END) as due_completed_count',
                [$startDate, $endDate, TaskStatus::COMPLETED->value, $endDate]
            )
            ->selectRaw(
                'SUM(CASE WHEN task_assignment_items.status = ? AND task_assignment_items.due_date IS NOT NULL AND task_assignment_items.completed_at IS NOT NULL AND task_assignment_items.completed_at <= task_assignment_items.due_date THEN 1 ELSE 0 END) as on_time_count',
                [TaskStatus::COMPLETED->value]
            )
            ->selectRaw(
                'SUM(CASE WHEN task_assignment_items.status = ? AND task_assignment_items.due_date IS NOT NULL AND task_assignment_items.completed_at IS NOT NULL THEN 1 ELSE 0 END) as dated_completed_count',
                [TaskStatus::COMPLETED->value]
            )
            ->selectRaw(
                'SUM(CASE WHEN task_assignment_items.requires_review = 0 OR task_assignment_items.status = ? THEN 1 ELSE 0 END) as approved_items_count',
                [TaskStatus::COMPLETED->value]
            )
            ->selectRaw('SUM(COALESCE(task_assignment_items.weight, 0)) as assigned_weight_sum')
            ->selectRaw(
                'SUM(CASE WHEN task_assignment_items.requires_review = 0 OR task_assignment_items.status = ? THEN COALESCE(task_assignment_items.weight, 0) ELSE 0.0 END) as weight_sum',
                [TaskStatus::COMPLETED->value]
            )
            ->selectRaw(
                'SUM(CASE WHEN task_assignment_items.requires_review = 0 OR task_assignment_items.status = ? THEN COALESCE(task_assignment_items.score, 0) ELSE 0.0 END) as score_sum',
                [TaskStatus::COMPLETED->value]
            )
            ->groupBy('task_assignments.employee_id');
    }

    /**
     * Period and status filters applied to task assignment items.
     */
    private static function applyItemFilters(Builder $query, array $filters): void
    {
        [$startDate, $endDate] = self::resolveDateRange($filters);

        // Items due or completed inside the period, open overdue items whose
        // due date is not after the period end, and open items without a due
        // date whose assignment date falls inside the period. Undated items
        // never count as overdue and never enter the completion-rate
        // denominator.
        $query->where(function (Builder $period) use ($startDate, $endDate) {
            $period->whereBetween('task_assignment_items.due_date', [$startDate, $endDate])
                ->orWhereBetween('task_assignment_items.completed_at', [$startDate, $endDate])
                ->orWhere(function (Builder $pendingReview) use ($startDate, $endDate) {
                    $pendingReview->where('task_assignment_items.status', TaskStatus::PENDING_REVIEW->value)
                        ->whereBetween('task_assignment_items.submitted_at', ["{$startDate} 00:00:00", "{$endDate} 23:59:59"]);
                })
                ->orWhere(function (Builder $overdue) use ($endDate) {
                    $overdue->whereIn('task_assignment_items.status', TaskStatus::employeeOpenValues())
                        ->whereNotNull('task_assignment_items.due_date')
                        ->whereDate('task_assignment_items.due_date', '<', today())
                        ->whereDate('task_assignment_items.due_date', '<=', $endDate);
                })
                ->orWhere(function (Builder $undated) use ($startDate, $endDate) {
                    $undated->whereNull('task_assignment_items.due_date')
                        ->whereNull('task_assignment_items.completed_at')
                        ->whereIn('task_assignment_items.status', [
                            TaskStatus::PENDING->value,
                            TaskStatus::IN_PROGRESS->value,
                            TaskStatus::RETURNED_FOR_REVISION->value,
                            TaskStatus::PENDING_REVIEW->value,
                            TaskStatus::TRANSFERRED->value,
                            TaskStatus::ESCALATED->value,
                            TaskStatus::POSTPONED->value,
                        ])
                        ->whereHas('taskAssignment', fn (Builder $assignment) => $assignment
                            ->whereBetween('task_assignments.assignment_date', [$startDate, $endDate]));
                });
        });

        $statuses = FilterBarService::values($filters['status'] ?? null, ['all']);

        if ($statuses !== []) {
            $query->whereIn('task_assignment_items.status', $statuses);
        }
    }

    /**
     * Company, branch and search filters applied to employees.
     */
    private static function applyEmployeeFilters(Builder $query, array $filters): void
    {
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $group) use ($search) {
                $group->where('employees.employee_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('details', fn (Builder $details) => $details
                        ->where('national_id', 'like', "%{$search}%")
                        ->orWhere('iqama_number', 'like', "%{$search}%"));
            });
        }

        $companyIds = array_filter((array) FilterBarService::values($filters['company_id'] ?? null));

        if ($companyIds !== []) {
            $query->where(function (Builder $cq) use ($companyIds): void {
                $cq->whereIn('employees.company_id', $companyIds)
                    ->orWhereExists(function ($sub) use ($companyIds): void {
                        $sub->selectRaw('1')
                            ->from('task_assignment_items')
                            ->join('task_assignments', 'task_assignments.id', '=', 'task_assignment_items.task_assignment_id')
                            ->whereColumn('task_assignments.employee_id', 'employees.id')
                            ->whereIn('task_assignment_items.company_id', $companyIds);
                    });
            });
        }

        $branchIds = array_filter((array) FilterBarService::values($filters['branch_id'] ?? null));

        if ($branchIds !== []) {
            $query->whereIn('employees.branch_id', $branchIds);
        }

        if (filled($filters['employee_id'] ?? null)) {
            $query->whereKey((int) $filters['employee_id']);
        }

        $employeeIds = array_values(array_filter(array_map('intval', (array) ($filters['employee_ids'] ?? []))));

        if ($employeeIds !== []) {
            $query->whereIn('employees.id', $employeeIds);
        }
    }
}
