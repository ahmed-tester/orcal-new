<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Employee;
use App\Models\ServiceTransaction;
use App\Models\SupervisionShift;
use App\Models\TaskAssignment;
use App\Models\TaskAssignmentItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

/**
 * Read-only supervisor performance report.
 *
 * The supervision tab links task assignment items to the supervision/shift
 * record that covers their due date. The employee tab reuses the existing
 * employee performance summaries for the supervisor's own record. Both tabs
 * share the company, supervisor and month selection but keep independent
 * calculations.
 */
class SupervisorPerformanceService
{
    public const TYPE_ALL = 'all';

    public const METRIC_DUE = 'due';

    public const METRIC_ON_TIME = 'on_time';

    public const METRIC_LATE = 'late';

    public const METRIC_OPEN = 'open';

    public const METRIC_PENDING_REVIEW = 'pending_review';

    public const METRIC_EXCLUDED = 'excluded';

    public const ITEM_ON_TIME = 'on_time';

    public const ITEM_LATE = 'late';

    public const ITEM_OPEN = 'open';

    public const ITEM_PENDING_REVIEW = 'pending_review';

    public const ITEM_MISSING_DATE = 'missing_date';

    public const ITEM_AMBIGUOUS = 'ambiguous';

    /**
     * Only these statuses enter the measurement.
     */
    private const MEASURABLE_STATUSES = ['active', 'completed'];

    /**
     * The report is an internal administrative entry point and stays outside
     * the production environment, matching the existing employee performance
     * screens.
     */
    public static function canViewReport(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        if (! $user->can('view_supervisor_performance_report')) {
            return false;
        }

        if (self::authorizedCompanyIds($user) === []) {
            return false;
        }

        // SupervisionShift has no historical branch column, so the first
        // version is limited to viewers without a branch restriction.
        return CompanyDepartmentPerformanceService::resolveBranchId([], $user) === null;
    }

    public static function canViewTeamTaskData(): bool
    {
        return self::canViewReport() && Gate::allows('viewAny', TaskAssignment::class);
    }

    /**
     * @return array<int>
     */
    public static function authorizedCompanyIds(?User $user = null): array
    {
        return CompanyDepartmentPerformanceService::getAuthorizedCompanyIds($user);
    }

    public static function normalizeMonth(?string $month): Carbon
    {
        return TaskAssignmentSummaryService::monthStart($month);
    }

    /**
     * Supervision records visible in the filters, with every number and
     * statement built from the same pass.
     *
     * @return array{
     *     month: string,
     *     current_month: bool,
     *     evaluation_through: string,
     *     team_tasks_visible: bool,
     *     rows: array<int, array<string, mixed>>,
     *     totals: array<string, mixed>,
     *     evaluation: array{reason: string|null, team: float|null, personal: float|null, total: float|null},
     *     employee: array<string, mixed>
     * }
     */
    public static function report(array $filters, bool $includeEmployeeTab = false): array
    {
        $user = auth()->user();
        $companyIds = self::filteredCompanyIds($filters, $user);
        [$periodStart, $periodEnd] = self::period($filters);
        $supervisorId = filled($filters['supervisor_id'] ?? null) ? (int) $filters['supervisor_id'] : null;
        $type = filled($filters['type'] ?? null) ? (string) $filters['type'] : self::TYPE_ALL;
        $cap = self::measurableCap($periodEnd);
        $teamTasksVisible = $companyIds !== [] && self::canViewTeamTaskData();
        $evaluationReason = match (true) {
            $supervisorId === null => 'select_supervisor_company',
            count($companyIds) > 1 => 'select_company',
            $companyIds === [] => 'unavailable',
            $periodStart->isFuture() => 'future_month',
            ! $teamTasksVisible => 'unavailable',
            default => null,
        };

        $empty = [
            'month' => $periodStart->format('Y-m'),
            'current_month' => $periodStart->isCurrentMonth(),
            'evaluation_through' => $cap->toDateString(),
            'team_tasks_visible' => $teamTasksVisible,
            'rows' => [],
            'totals' => self::emptyTotals(),
            'evaluation' => ['reason' => $evaluationReason ?? 'unavailable', 'team' => null, 'personal' => null, 'total' => null],
            'employee' => self::unavailableEmployeeTab($supervisorId === null ? 'no_supervisor' : 'out_of_scope'),
        ];

        if ($companyIds === [] || ! self::canViewReport($user)) {
            return $empty;
        }

        $shifts = SupervisionShift::query()
            ->whereIn('company_id', $companyIds)
            ->when($supervisorId !== null, fn ($query) => $query->where('supervisor_id', $supervisorId))
            ->when($evaluationReason !== null && $type !== self::TYPE_ALL, fn ($query) => $query->where('type', $type))
            ->where(function ($query) use ($periodStart, $periodEnd, $supervisorId) {
                $query->where(function ($dated) use ($periodStart, $periodEnd) {
                    $dated->whereNotNull('start_date')
                        ->whereNotNull('end_date')
                        ->whereDate('end_date', '>=', $periodStart->toDateString())
                        ->whereDate('start_date', '<=', $periodEnd->toDateString());
                });

                // A selected supervisor also shows records without valid dates
                // as "not measurable" instead of hiding them.
                if ($supervisorId !== null) {
                    $query->orWhereNull('start_date')->orWhereNull('end_date');
                }
            })
            ->with(['supervisor.user', 'supervisor.jobTitle', 'supervisor.company', 'company', 'task'])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        if ($includeEmployeeTab) {
            $empty['employee'] = self::employeeTab($supervisorId, $filters);
        }

        if ($shifts->isEmpty()) {
            return $empty;
        }

        $competitorWindows = $teamTasksVisible ? self::competitorWindows($companyIds, $periodStart, $cap) : [];
        $windows = [];
        $teamsByShift = [];
        $visibleTeamIds = [];
        $measurableTeamIds = [];

        foreach ($shifts as $shift) {
            $team = self::teamIds($shift);
            $teamsByShift[$shift->id] = $team;
            $visibleTeamIds += array_flip($team);
            $window = self::windowFor($shift, $periodStart, $cap);

            if ($window !== null && $shift->supervisor_id !== null && in_array($shift->status, self::MEASURABLE_STATUSES, true)) {
                $windows[$shift->id] = $window;
                if ($teamTasksVisible) {
                    $measurableTeamIds += array_flip($team);
                }
            }
        }

        $teamCompanyIds = self::teamEmployeeCompanies($companyIds, array_keys($visibleTeamIds));
        $itemsByEmployee = self::candidateItems(array_keys($measurableTeamIds), $companyIds, $windows)
            ->groupBy('taskAssignment.employee_id');

        $rows = [];

        foreach ($shifts as $shift) {
            $team = $teamsByShift[$shift->id];
            $window = $windows[$shift->id] ?? null;
            $statusMeasurable = in_array($shift->status, self::MEASURABLE_STATUSES, true);
            $teamCount = self::teamCount($team, (int) $shift->company_id, $teamCompanyIds);

            $classified = collect();

            if ($teamTasksVisible && $window !== null && $team !== []) {
                $teamLookup = array_flip($team);
                $classified = collect($team)
                    ->flatMap(fn (int $employeeId) => $itemsByEmployee->get($employeeId, []))
                    ->filter(fn (TaskAssignmentItem $item) => self::itemMatchesShift(
                        $item,
                        (int) $shift->company_id,
                        $teamLookup,
                        $shift->task_id === null ? null : (int) $shift->task_id,
                        $window,
                    ))
                    ->sortBy(['due_date', 'id'])
                    ->map(fn (TaskAssignmentItem $item) => [
                        'item' => $item,
                        'state' => self::ambiguousFor($item, $competitorWindows) ? self::ITEM_AMBIGUOUS : self::classificationFor($item),
                    ]);
            }

            $counts = self::countStates($classified);
            $reason = self::rowReason($shift, $window, $statusMeasurable, $teamCount, $counts['due']);

            $rows[] = [
                'shift_id' => (int) $shift->id,
                'shift' => $shift,
                'window' => $window === null ? null : [
                    'start' => $window['start']->toDateString(),
                    'end' => $window['end']->toDateString(),
                ],
                'measurable' => $teamTasksVisible && $window !== null && $teamCount > 0,
                'provisional' => $shift->status === 'active',
                'reason' => $teamTasksVisible ? $reason : 'no_task_permission',
                'team_count' => $teamCount,
                'items' => $classified,
                'due' => $counts['due'],
                'on_time' => $counts['on_time'],
                'late' => $counts['late'],
                'open' => $counts['open'],
                'pending_review' => $counts['pending_review'],
                'missing_date' => $counts['missing_date'],
                'ambiguous' => $counts['ambiguous'],
                'rate' => $counts['due'] > 0 ? round(($counts['on_time'] / $counts['due']) * 100, 2) : null,
            ];
        }

        if ($evaluationReason === null) {
            $evaluationRows = array_filter($rows, fn (array $row) => in_array($row['shift']->type, ['supervision', 'shift'], true));
            $empty['evaluation'] = self::monthlyEvaluation($supervisorId, $companyIds[0], $periodStart, $cap, self::totals($evaluationRows));
        }

        if ($evaluationReason === null && $type !== self::TYPE_ALL) {
            $rows = array_values(array_filter($rows, fn (array $row) => $row['shift']->type === $type));
        }

        return array_replace($empty, ['rows' => $rows, 'totals' => self::totals($rows)]);
    }

    /**
     * Supervisors that have at least one supervision record inside the
     * authorized scope.
     *
     * @return array<int, array{id: int, label: string}>
     */
    public static function supervisorOptions(array $filters = [], ?User $user = null): array
    {
        $companyIds = self::filteredCompanyIds($filters, $user);

        if ($companyIds === []) {
            return [];
        }

        $supervisorIds = SupervisionShift::query()
            ->whereIn('company_id', $companyIds)
            ->whereNotNull('supervisor_id')
            ->select('supervisor_id')
            ->distinct()
            ->pluck('supervisor_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($supervisorIds === []) {
            return [];
        }

        return Employee::query()
            ->whereIn('id', $supervisorIds)
            ->with('user')
            ->get()
            ->sortBy(fn (Employee $employee) => $employee->user?->name ?? '')
            ->map(fn (Employee $employee) => [
                'id' => (int) $employee->id,
                'label' => trim(($employee->user?->name ?? '').' — '.($employee->employee_number ?? '')),
            ])
            ->values()
            ->all();
    }

    /**
     * Details of one supervisor as an employee, scoped to the same company and
     * month as the report.
     *
     * @return array<string, mixed>
     */
    private static function employeeTab(?int $supervisorId, array $filters): array
    {
        $user = auth()->user();
        $companyIds = self::filteredCompanyIds($filters, $user);
        [$periodStart, $periodEnd] = self::period($filters);
        $month = $periodStart->format('Y-m');

        if ($supervisorId === null || $user === null) {
            return self::unavailableEmployeeTab('no_supervisor');
        }

        $employee = CompanyAccessScopeService::apply(Employee::query()->nonTemporary())
            ->with(['user', 'company', 'jobTitle', 'department'])
            ->whereKey($supervisorId)
            ->first();

        if ($employee === null || ! EmployeePerformanceAccessService::canViewEmployeeRecord($user, $employee, $periodStart, $periodEnd)) {
            return self::unavailableEmployeeTab('out_of_scope');
        }

        if (! in_array((int) $employee->company_id, $companyIds, true)) {
            return self::unavailableEmployeeTab('company_mismatch');
        }

        $taskVisible = Gate::allows('viewAny', TaskAssignment::class)
            && Employee::query()->saudi()->whereKey($employee->getKey())->exists();
        $serviceVisible = Gate::allows('viewAny', ServiceTransaction::class);

        if (! $taskVisible && ! $serviceVisible) {
            return self::unavailableEmployeeTab('no_permission');
        }

        $filters = ['month' => $month, 'company_id' => (int) $employee->company_id];
        $summary = $taskVisible ? TaskAssignmentSummaryService::employeeRow($employee->id, $filters) : null;

        return [
            'available' => true,
            'reason' => null,
            'employee' => $employee,
            'task_visible' => $taskVisible,
            'service_visible' => $serviceVisible,
            'summary' => $summary,
            'items' => $taskVisible ? TaskAssignmentSummaryService::employeeItems($employee->id, $filters) : collect(),
            'weighted_percentage' => ($summary && (float) ($summary->weight_sum ?? 0) > 0)
                ? TaskAssignmentSummaryService::weightedPercentage($summary)
                : null,
            'completion_rate' => $summary ? TaskAssignmentSummaryService::completionRate($summary) : null,
            'on_time_rate' => $summary ? TaskAssignmentSummaryService::onTimeRate($summary) : null,
            'services' => $serviceVisible ? [
                'stats' => ServicePerformanceSummaryService::stats($employee->id, $filters, $employee),
                'transactions' => ServicePerformanceSummaryService::transactions($employee->id, $filters, null, $employee),
            ] : null,
        ];
    }

    private static function unavailableEmployeeTab(string $reason): array
    {
        return ['available' => false, 'reason' => $reason];
    }

    /**
     * @return array{reason: string|null, team: float|null, personal: float|null, total: float|null}
     */
    private static function monthlyEvaluation(int $supervisorId, int $companyId, Carbon $start, Carbon $end, array $team): array
    {
        $unavailable = ['reason' => 'unavailable', 'team' => null, 'personal' => null, 'total' => null];

        if ($team['due'] === 0) {
            return array_replace($unavailable, ['reason' => 'no_team_items']);
        }

        $employee = CompanyAccessScopeService::apply(Employee::query()->nonTemporary())
            ->where('employees.company_id', $companyId)
            ->find($supervisorId);
        $user = auth()->user();

        if ($employee === null || $user === null || ! EmployeePerformanceAccessService::canViewTaskData($user, $employee, $start, $end)) {
            return $unavailable;
        }

        $assignments = TaskAssignmentSummaryService::assignments(['month' => $start->format('Y-m'), 'company_id' => $companyId])
            ->where('employee_id', $supervisorId)
            ->select('task_assignments.id');
        $personalQuery = TaskAssignmentItem::query()
            ->whereIn('task_assignment_id', $assignments)
            ->where('due_date', '>=', $start->toDateString())
            ->where('due_date', '<', $end->copy()->addDay()->toDateString())
            ->where(fn ($query) => $query->where('status', '!=', TaskStatus::COMPLETED->value)->orWhereNotNull('completed_at'));

        $personal = self::excludeOpenItemsDueToday($personalQuery, $end)
            ->selectRaw('COUNT(*) AS due, SUM(CASE WHEN status = ? AND DATE(completed_at) <= DATE(due_date) THEN 1 ELSE 0 END) AS on_time', [TaskStatus::COMPLETED->value])
            ->toBase()
            ->first();

        if ((int) $personal->due === 0) {
            return array_replace($unavailable, ['reason' => 'no_personal_items']);
        }

        $teamRate = $team['on_time'] / $team['due'] * 100;
        $personalRate = $personal->on_time / $personal->due * 100;

        return [
            'reason' => null,
            'team' => $teamRate,
            'personal' => $personalRate,
            'total' => ($teamRate + $personalRate) / 2,
        ];
    }

    /**
     * Items of a row statement filtered by the clicked metric.
     *
     * @return Collection<int, array{item: TaskAssignmentItem, state: string}>
     */
    public static function statementItems(array $row, string $metric): Collection
    {
        /** @var Collection<int, array{item: TaskAssignmentItem, state: string}> $items */
        $items = $row['items'] ?? collect();

        return match ($metric) {
            self::METRIC_ON_TIME => $items->where('state', self::ITEM_ON_TIME),
            self::METRIC_LATE => $items->where('state', self::ITEM_LATE),
            self::METRIC_OPEN => $items->where('state', self::ITEM_OPEN),
            self::METRIC_PENDING_REVIEW => $items->where('state', self::ITEM_PENDING_REVIEW),
            self::METRIC_EXCLUDED => $items->filter(fn (array $item) => in_array($item['state'], [self::ITEM_AMBIGUOUS, self::ITEM_MISSING_DATE], true)),
            default => $items->filter(fn (array $item) => in_array($item['state'], [self::ITEM_ON_TIME, self::ITEM_LATE, self::ITEM_OPEN, self::ITEM_PENDING_REVIEW], true)),
        };
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function period(array $filters): array
    {
        [$start, $end] = TaskAssignmentSummaryService::resolveDateRange([
            'month' => self::normalizeMonth($filters['month'] ?? null)->format('Y-m'),
        ]);

        return [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->startOfDay()];
    }

    /**
     * @return array<int>
     */
    public static function filteredCompanyIds(array $filters, ?User $user = null): array
    {
        $ids = self::authorizedCompanyIds($user);
        $selected = array_map('intval', FilterBarService::values($filters['company_id'] ?? null));

        if ($selected !== []) {
            $ids = array_values(array_intersect($ids, $selected));
        }

        return array_values(array_map('intval', $ids));
    }

    private static function measurableCap(Carbon $periodEnd): Carbon
    {
        return $periodEnd->min(today());
    }

    private static function excludeOpenItemsDueToday(Builder $query, Carbon $end): Builder
    {
        if (! $end->isToday()) {
            return $query;
        }

        $date = $end->toDateString();
        $nextDay = $end->copy()->addDay()->toDateString();

        return $query->where(fn (Builder $items) => $items
            ->where('task_assignment_items.due_date', '<', $date)
            ->orWhere(fn (Builder $completed) => $completed
                ->where('task_assignment_items.status', TaskStatus::COMPLETED->value)
                ->where('task_assignment_items.completed_at', '<', $nextDay)));
    }

    /**
     * @return array{start: Carbon, end: Carbon}|null
     */
    private static function windowFor(SupervisionShift $shift, Carbon $periodStart, Carbon $cap): ?array
    {
        if ($shift->start_date === null || $shift->end_date === null) {
            return null;
        }

        $start = $shift->start_date->copy()->startOfDay();
        $end = $shift->end_date->copy()->startOfDay();

        if ($start->greaterThan($end)) {
            return null;
        }

        if ($start->lessThan($periodStart)) {
            $start = $periodStart->copy();
        }

        if ($end->greaterThan($cap)) {
            $end = $cap->copy();
        }

        if ($start->greaterThan($end)) {
            return null;
        }

        return ['start' => $start, 'end' => $end];
    }

    /**
     * @return array<int>
     */
    private static function teamIds(SupervisionShift $shift): array
    {
        $team = array_map('intval', (array) ($shift->assigned_employees ?? []));
        $team = array_values(array_unique($team));

        return array_values(array_filter(
            $team,
            fn (int $id) => $id > 0 && $id !== (int) $shift->supervisor_id,
        ));
    }

    /**
     * @param  array<int>  $team
     * @param  array<int, int>  $teamCompanies
     */
    private static function teamCount(array $team, int $companyId, array $teamCompanies): int
    {
        $count = 0;

        foreach ($team as $employeeId) {
            if ((int) ($teamCompanies[$employeeId] ?? 0) === $companyId) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Company of every team member used by the report, so team size follows
     * the employee company and excludes any id from another company.
     *
     * @param  array<int>  $companyIds
     * @param  array<int>  $employeeIds
     * @return array<int, int>
     */
    private static function teamEmployeeCompanies(array $companyIds, array $employeeIds): array
    {
        if ($employeeIds === []) {
            return [];
        }

        return Employee::query()
            ->whereIn('company_id', $companyIds)
            ->whereIntegerInRaw('id', $employeeIds)
            ->pluck('company_id', 'id')
            ->map(fn ($companyId) => (int) $companyId)
            ->all();
    }

    /**
     * All measurable records of the authorized companies inside the period,
     * regardless of the selected supervisor or type, used to detect overlap.
     *
     * @param  array<int>  $companyIds
     * @return array<int, array<int, array{shift_id: int, task_id: int|null, team: array<int, int>, start: Carbon, end: Carbon}>>
     */
    private static function competitorWindows(array $companyIds, Carbon $periodStart, Carbon $cap): array
    {
        $shifts = SupervisionShift::query()
            ->whereIn('company_id', $companyIds)
            ->whereIn('status', self::MEASURABLE_STATUSES)
            ->whereNotNull('supervisor_id')
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '>=', $periodStart->toDateString())
            ->whereDate('start_date', '<=', $cap->toDateString())
            ->get(['id', 'company_id', 'supervisor_id', 'task_id', 'assigned_employees', 'start_date', 'end_date', 'status']);

        $windows = [];

        foreach ($shifts as $shift) {
            $window = self::windowFor($shift, $periodStart, $cap);
            $team = self::teamIds($shift);

            if ($window === null || $team === []) {
                continue;
            }

            $windows[(int) $shift->company_id][] = [
                'shift_id' => (int) $shift->id,
                'task_id' => $shift->task_id === null ? null : (int) $shift->task_id,
                'team' => array_flip($team),
                'start' => $window['start'],
                'end' => $window['end'],
            ];
        }

        return $windows;
    }

    /**
     * One query for every item inside the measurable windows of the selected
     * records, instead of loading items per record.
     *
     * @param  array<int>  $teamIds
     * @param  array<int>  $companyIds
     * @param  array<int, array{start: Carbon, end: Carbon}>  $windows
     * @return Collection<int, TaskAssignmentItem>
     */
    private static function candidateItems(array $teamIds, array $companyIds, array $windows): Collection
    {
        if ($teamIds === [] || $windows === []) {
            return collect();
        }

        $starts = array_map(fn (array $window) => $window['start'], $windows);
        $ends = array_map(fn (array $window) => $window['end'], $windows);

        $end = max($ends);
        $query = TaskAssignmentItem::query()
            ->select('task_assignment_items.*')
            ->join('task_assignments', 'task_assignments.id', '=', 'task_assignment_items.task_assignment_id')
            ->join('employees', 'employees.id', '=', 'task_assignments.employee_id')
            ->whereIntegerInRaw('task_assignments.employee_id', $teamIds)
            ->whereIn('employees.company_id', $companyIds)
            ->where('task_assignment_items.due_date', '>=', min($starts)->toDateString())
            ->where('task_assignment_items.due_date', '<', $end->copy()->addDay()->toDateString());

        return self::excludeOpenItemsDueToday($query, $end)
            ->with(['task', 'taskAssignment.employee.user'])
            ->orderBy('task_assignment_items.due_date')
            ->orderBy('task_assignment_items.id')
            ->get();
    }

    /**
     * @param  array<int, int>  $team
     * @param  array{start: Carbon, end: Carbon}  $window
     */
    private static function itemMatchesShift(
        TaskAssignmentItem $item,
        int $companyId,
        array $team,
        ?int $taskId,
        array $window,
    ): bool {
        $employeeId = (int) ($item->taskAssignment?->employee_id ?? 0);

        if ($employeeId === 0 || ! isset($team[$employeeId])) {
            return false;
        }

        if ((int) ($item->taskAssignment?->employee?->company_id ?? 0) !== $companyId) {
            return false;
        }

        if ($item->due_date === null
            || $item->due_date->lessThan($window['start'])
            || $item->due_date->greaterThan($window['end'])) {
            return false;
        }

        return $taskId === null || (int) $item->task_id === $taskId;
    }

    /**
     * An item whose due date falls inside more than one measurable record of
     * the same company, team and task is left outside the indicator.
     *
     * @param  array<int, array<int, array{shift_id: int, task_id: int|null, team: array<int, int>, start: Carbon, end: Carbon}>>  $competitorWindows
     */
    private static function ambiguousFor(TaskAssignmentItem $item, array $competitorWindows): bool
    {
        $employeeId = (int) ($item->taskAssignment?->employee_id ?? 0);
        $companyId = (int) ($item->taskAssignment?->employee?->company_id ?? 0);

        if ($employeeId === 0 || $companyId === 0 || $item->due_date === null) {
            return false;
        }

        $matches = 0;

        foreach ($competitorWindows[$companyId] ?? [] as $candidate) {
            if (! isset($candidate['team'][$employeeId])) {
                continue;
            }

            if ($item->due_date->lessThan($candidate['start']) || $item->due_date->greaterThan($candidate['end'])) {
                continue;
            }

            if ($candidate['task_id'] !== null && (int) $item->task_id !== $candidate['task_id']) {
                continue;
            }

            $matches++;

            if ($matches > 1) {
                return true;
            }
        }

        return false;
    }

    private static function classificationFor(TaskAssignmentItem $item): string
    {
        if ($item->status === TaskStatus::COMPLETED) {
            if ($item->completed_at === null) {
                return self::ITEM_MISSING_DATE;
            }

            return $item->due_date !== null && $item->completed_at->lessThanOrEqualTo($item->due_date)
                ? self::ITEM_ON_TIME
                : self::ITEM_LATE;
        }

        if ($item->status === TaskStatus::PENDING_REVIEW) {
            return self::ITEM_PENDING_REVIEW;
        }

        return self::ITEM_OPEN;
    }

    /**
     * @param  Collection<int, array{item: TaskAssignmentItem, state: string}>  $classified
     * @return array<string, int>
     */
    private static function countStates(Collection $classified): array
    {
        $counts = [
            'due' => 0,
            'on_time' => 0,
            'late' => 0,
            'open' => 0,
            'pending_review' => 0,
            'missing_date' => 0,
            'ambiguous' => 0,
        ];

        foreach ($classified as $entry) {
            $state = $entry['state'];

            if ($state === self::ITEM_MISSING_DATE || $state === self::ITEM_AMBIGUOUS) {
                $counts[$state]++;

                continue;
            }

            $counts['due']++;
            $counts[$state]++;
        }

        return $counts;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    private static function totals(array $rows): array
    {
        $totals = self::emptyTotals();

        foreach ($rows as $row) {
            $totals['due'] += $row['due'];
            $totals['on_time'] += $row['on_time'];
            $totals['late'] += $row['late'];
            $totals['open'] += $row['open'];
            $totals['pending_review'] += $row['pending_review'];
            $totals['missing_date'] += $row['missing_date'];
            $totals['ambiguous'] += $row['ambiguous'];
        }

        $totals['excluded'] = $totals['missing_date'] + $totals['ambiguous'];
        $totals['rate'] = $totals['due'] > 0
            ? round(($totals['on_time'] / $totals['due']) * 100, 2)
            : null;

        return $totals;
    }

    /**
     * @return array<string, mixed>
     */
    private static function emptyTotals(): array
    {
        return [
            'due' => 0,
            'on_time' => 0,
            'late' => 0,
            'open' => 0,
            'pending_review' => 0,
            'missing_date' => 0,
            'ambiguous' => 0,
            'excluded' => 0,
            'rate' => null,
        ];
    }

    private static function rowReason(
        SupervisionShift $shift,
        ?array $window,
        bool $statusMeasurable,
        int $teamCount,
        int $due,
    ): ?string {
        if ($shift->supervisor_id === null) {
            return 'no_supervisor';
        }

        if (! $statusMeasurable) {
            return 'status_excluded';
        }

        if ($shift->start_date === null || $shift->end_date === null
            || $shift->start_date->greaterThan($shift->end_date)) {
            return 'invalid_dates';
        }

        if ($window === null) {
            return 'no_measurable_days';
        }

        if ($teamCount === 0) {
            return 'no_team';
        }

        if ($due === 0) {
            return 'no_due_items';
        }

        return null;
    }
}
