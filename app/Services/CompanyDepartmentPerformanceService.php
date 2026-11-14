<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Filament\Resources\ForeignEmployees\ForeignEmployeeResource;
use App\Filament\Resources\SaudiEmployees\SaudiEmployeeResource;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ServiceTransaction;
use App\Models\TaskAssignmentItem;
use App\Models\UnifiedDeficiencyTask;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CompanyDepartmentPerformanceService
{
    /**
     * Resolve date range from filters array, defaulting to current month start and end.
     *
     * @return array{0: string, 1: string}
     */
    public static function resolveDateRange(array $filters): array
    {
        return TaskAssignmentSummaryService::resolveDateRange($filters);
    }

    /**
     * Authorized companies for the given user.
     *
     * @return array<int>
     */
    public static function getAuthorizedCompanyIds(?User $user = null): array
    {
        $user = $user ?? auth()->user();

        if (! $user) {
            return [];
        }

        if ($user->isSuperAdmin()) {
            return Company::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        if ($user->isOwner()) {
            return $user->ownedCompanies()->pluck('companies.id')->map(fn ($id) => (int) $id)->all();
        }

        return array_map('intval', CompanyFormService::getAuthorizedCompanyIds($user));
    }

    /**
     * Check if user is a supervisor.
     */
    public static function isSupervisorViewer(?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        return $user !== null && EmployeePerformanceAccessService::isSupervisor($user);
    }

    /**
     * Resolve effective branch ID considering user restrictions and explicit filter.
     */
    public static function resolveBranchId(array $filters, ?User $user = null): ?int
    {
        $user = $user ?? auth()->user();

        if ($user && $user->branch_id && ! $user->canAccessAllBranches()) {
            return (int) $user->branch_id;
        }

        if (filled($filters['branch_id'] ?? null)) {
            return (int) $filters['branch_id'];
        }

        return null;
    }

    /**
     * Team employee IDs for supervisor viewer during the date range.
     *
     * @return array<int>
     */
    public static function supervisorTeamIds(?User $user, string $startDate, string $endDate): array
    {
        if (! $user || ! self::isSupervisorViewer($user)) {
            return [];
        }

        $windows = EmployeePerformanceAccessService::teamWindows(
            $user,
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        );

        $teamIds = [];
        foreach ($windows as $window) {
            $teamIds = array_merge($teamIds, $window['employees']);
        }

        if ($user->employee?->id) {
            $teamIds[] = (int) $user->employee->id;
        }

        return array_values(array_unique(array_map('intval', $teamIds)));
    }

    /**
     * Overall KPI stats for the top summary cards.
     */
    public static function getOverallStats(array $filters = []): object
    {
        $user = auth()->user();
        $authorizedCompanyIds = self::getAuthorizedCompanyIds($user);

        if ($authorizedCompanyIds === []) {
            return (object) [
                ...self::metrics(null, null, null),
                'companies_count' => 0,
                'departments_count' => 0,
                'employees_count' => 0,
            ];
        }

        [$startDate, $endDate] = self::resolveDateRange($filters);
        $branchId = self::resolveBranchId($filters, $user);
        $scopedCompanyIds = filled($filters['company_id'] ?? null)
            ? array_values(array_intersect($authorizedCompanyIds, (array) $filters['company_id']))
            : $authorizedCompanyIds;

        if ($scopedCompanyIds === []) {
            $scopedCompanyIds = [0];
        }

        $teamIds = self::isSupervisorViewer($user) ? self::supervisorTeamIds($user, $startDate, $endDate) : null;

        // Services summary
        $serviceRow = self::selectServiceMetrics(
            self::servicesAggregateQuery($scopedCompanyIds, $branchId, $teamIds),
            $startDate,
            $endDate,
        )->first();

        // Deficiencies summary
        $deficiencyRow = self::deficienciesAggregateQuery($scopedCompanyIds, $branchId, $startDate, $endDate, $teamIds)
            ->selectRaw('COUNT(*) as open_deficiencies')
            ->first();

        // Tasks summary
        $taskRow = self::selectTaskMetrics(
            self::tasksAggregateQuery($scopedCompanyIds, $branchId, $teamIds),
            $startDate,
            $endDate,
        )->first();

        // Counts
        $companiesCount = count($scopedCompanyIds);
        $departmentsCount = Department::query()
            ->where(function (Builder $q) use ($scopedCompanyIds) {
                $q->whereIn('company_id', $scopedCompanyIds)
                    ->orWhereHas('companies', fn (Builder $sub) => $sub->whereIn('companies.id', $scopedCompanyIds));
            })
            ->count();

        $employeesCountQuery = Employee::query()
            ->nonTemporary()
            ->whereIn('company_id', $scopedCompanyIds);

        if ($branchId !== null) {
            $employeesCountQuery->where('branch_id', $branchId);
        }

        if ($teamIds !== null) {
            $employeesCountQuery->whereIn('id', $teamIds);
        }

        return (object) [
            ...self::metrics($serviceRow, $deficiencyRow, $taskRow),
            'companies_count' => $companiesCount,
            'departments_count' => $departmentsCount,
            'employees_count' => $employeesCountQuery->count(),
        ];
    }

    /**
     * Level 1: Companies Summary Table.
     *
     * @return Collection<int, object>
     */
    public static function getCompaniesSummary(array $filters = []): Collection
    {
        $user = auth()->user();
        $authorizedCompanyIds = self::getAuthorizedCompanyIds($user);

        if ($authorizedCompanyIds === []) {
            return collect();
        }

        [$startDate, $endDate] = self::resolveDateRange($filters);
        $branchId = self::resolveBranchId($filters, $user);
        $scopedCompanyIds = filled($filters['company_id'] ?? null)
            ? array_values(array_intersect($authorizedCompanyIds, (array) $filters['company_id']))
            : $authorizedCompanyIds;

        if ($scopedCompanyIds === []) {
            return collect();
        }

        $companies = Company::query()
            ->whereIn('id', $scopedCompanyIds)
            ->when(filled($filters['search'] ?? null), function (Builder $q) use ($filters) {
                $term = trim($filters['search']);
                $q->where(fn (Builder $sub) => $sub->where('trade_name', 'like', "%{$term}%")
                    ->orWhere('trade_name_en', 'like', "%{$term}%")
                    ->orWhere('company_number', 'like', "%{$term}%"));
            })
            ->orderBy('trade_name')
            ->get(['id', 'trade_name', 'trade_name_en', 'company_number']);

        if ($companies->isEmpty()) {
            return collect();
        }

        $scopedCompanyIds = $companies->modelKeys();
        $teamIds = self::isSupervisorViewer($user) ? self::supervisorTeamIds($user, $startDate, $endDate) : null;

        // 1. Services by company_id
        $servicesMap = self::selectServiceMetrics(
            self::servicesAggregateQuery($scopedCompanyIds, $branchId, $teamIds)
                ->selectRaw('service_transactions.company_id'),
            $startDate,
            $endDate,
        )->groupBy('service_transactions.company_id')
            ->get()
            ->keyBy('company_id');

        // 2. Deficiencies by company_id
        $deficienciesMap = self::deficienciesAggregateQuery($scopedCompanyIds, $branchId, $startDate, $endDate, $teamIds)
            ->selectRaw('
                unified_deficiency_tasks.company_id,
                COUNT(*) as open_deficiencies
            ')
            ->groupBy('unified_deficiency_tasks.company_id')
            ->get()
            ->keyBy('company_id');

        // 3. Tasks by employees.company_id
        $tasksMap = self::selectTaskMetrics(
            self::tasksAggregateQuery($scopedCompanyIds, $branchId, $teamIds)
                ->selectRaw('employees.company_id'),
            $startDate,
            $endDate,
        )->groupBy('employees.company_id')
            ->get()
            ->keyBy('company_id');

        // 4. Counts
        $employeesCountMap = Employee::query()
            ->nonTemporary()
            ->whereIn('company_id', $scopedCompanyIds)
            ->when($branchId !== null, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->when($teamIds !== null, fn (Builder $q) => $q->whereIn('id', $teamIds))
            ->selectRaw('company_id, COUNT(*) as count')
            ->groupBy('company_id')
            ->pluck('count', 'company_id');

        $departmentsCountMap = Department::query()
            ->whereIn('company_id', $scopedCompanyIds)
            ->selectRaw('company_id, COUNT(*) as count')
            ->groupBy('company_id')
            ->pluck('count', 'company_id');

        return $companies->map(function (Company $company) use (
            $servicesMap,
            $deficienciesMap,
            $tasksMap,
            $employeesCountMap,
            $departmentsCountMap
        ) {
            $cid = $company->id;
            $srv = $servicesMap->get($cid);
            $def = $deficienciesMap->get($cid);
            $tsk = $tasksMap->get($cid);

            return (object) [
                'company_id' => $cid,
                'company_name' => $company->trade_name ?: ($company->trade_name_en ?: __('performance.fallback.company', ['id' => $cid])),
                'company_number' => $company->company_number,
                ...self::metrics($srv, $def, $tsk),
                'departments_count' => (int) ($departmentsCountMap[$cid] ?? 0),
                'employees_count' => (int) ($employeesCountMap[$cid] ?? 0),
            ];
        });
    }

    /**
     * Level 2: Departments Summary for a single company.
     *
     * @return Collection<int, object>
     */
    public static function getDepartmentsSummary(int $companyId, array $filters = []): Collection
    {
        $user = auth()->user();
        $authorizedCompanyIds = self::getAuthorizedCompanyIds($user);

        if (! in_array($companyId, $authorizedCompanyIds, true)) {
            return collect();
        }

        [$startDate, $endDate] = self::resolveDateRange($filters);
        $branchId = self::resolveBranchId($filters, $user);
        $teamIds = self::isSupervisorViewer($user) ? self::supervisorTeamIds($user, $startDate, $endDate) : null;

        // 1. Services aggregated by executor department
        $servicesMap = self::selectServiceMetrics(
            self::servicesAggregateQuery([$companyId], $branchId, $teamIds)
                ->leftJoin('employees as executor_emp', 'service_transactions.executed_by', '=', 'executor_emp.id')
                ->selectRaw('COALESCE(executor_emp.department_id, 0) as department_id'),
            $startDate,
            $endDate,
        )->groupBy(DB::raw('COALESCE(executor_emp.department_id, 0)'))
            ->get()
            ->keyBy('department_id');

        // 2. Deficiencies aggregated by responsible employee department
        $deficienciesMap = self::deficienciesAggregateQuery([$companyId], $branchId, $startDate, $endDate, $teamIds)
            ->leftJoin('employees as resp_emp', 'unified_deficiency_tasks.responsible_employee_id', '=', 'resp_emp.id')
            ->selectRaw('
                COALESCE(resp_emp.department_id, 0) as department_id,
                COUNT(*) as open_deficiencies
            ')
            ->groupBy(DB::raw('COALESCE(resp_emp.department_id, 0)'))
            ->get()
            ->keyBy('department_id');

        // 3. Tasks aggregated by employee department
        $tasksMap = self::selectTaskMetrics(
            self::tasksAggregateQuery([$companyId], $branchId, $teamIds)
                ->selectRaw('COALESCE(employees.department_id, 0) as department_id'),
            $startDate,
            $endDate,
        )->groupBy(DB::raw('COALESCE(employees.department_id, 0)'))
            ->get()
            ->keyBy('department_id');

        // 4. Employees count by department
        $employeesCountMap = Employee::query()
            ->nonTemporary()
            ->where('company_id', $companyId)
            ->when($branchId !== null, fn (Builder $q) => $q->where('branch_id', $branchId))
            ->when($teamIds !== null, fn (Builder $q) => $q->whereIn('id', $teamIds))
            ->selectRaw('COALESCE(department_id, 0) as department_id, COUNT(*) as count')
            ->groupBy(DB::raw('COALESCE(department_id, 0)'))
            ->pluck('count', 'department_id');

        // Fetch registered departments
        $departments = Department::query()
            ->where(function (Builder $q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->orWhereHas('companies', fn (Builder $sub) => $sub->where('companies.id', $companyId));
            })
            ->with('parent')
            ->when(filled($filters['search'] ?? null), function (Builder $q) use ($filters) {
                $term = trim($filters['search']);
                $q->where('name', 'like', "%{$term}%");
            })
            ->orderBy('name')
            ->get();

        $isSupervisor = $teamIds !== null;
        $result = collect();

        foreach ($departments as $department) {
            $did = $department->id;
            $srv = $servicesMap->get($did);
            $def = $deficienciesMap->get($did);
            $tsk = $tasksMap->get($did);

            $displayName = $department->parent
                ? "{$department->name} ({$department->parent->name})"
                : $department->name;

            $result->push((object) [
                'department_id' => $did,
                'department_name' => $displayName,
                'is_unassigned' => false,
                'is_team_scope' => $isSupervisor,
                ...self::metrics($srv, $def, $tsk),
                'employees_count' => (int) ($employeesCountMap[$did] ?? 0),
            ]);
        }

        // Check if there are unassigned records (department_id = 0)
        $unassignedSrv = $servicesMap->get(0);
        $unassignedDef = $deficienciesMap->get(0);
        $unassignedTsk = $tasksMap->get(0);
        $unassignedEmpCount = (int) ($employeesCountMap[0] ?? 0);

        $hasUnassigned = ($unassignedSrv && ($unassignedSrv->active_services || $unassignedSrv->completed_services || $unassignedSrv->overdue_services))
            || ($unassignedDef && $unassignedDef->open_deficiencies)
            || ($unassignedTsk && ($unassignedTsk->task_due_in_period || $unassignedTsk->task_completed_in_period || $unassignedTsk->task_overdue))
            || $unassignedEmpCount > 0;

        if ($hasUnassigned) {

            $result->push((object) [
                'department_id' => 'unassigned',
                'department_name' => __('performance.levels.unassigned_or_no_department'),
                'is_unassigned' => true,
                'is_team_scope' => false,
                ...self::metrics($unassignedSrv, $unassignedDef, $unassignedTsk),
                'employees_count' => $unassignedEmpCount,
            ]);
        }

        return $result;
    }

    /**
     * Resolve department leads / supervisors by department IDs.
     *
     * @param  array<int|string>  $departmentIds
     * @return Collection<int|string, object>
     */
    public static function getDepartmentLeads(int $companyId, array $departmentIds): Collection
    {
        $deptIds = array_values(array_filter($departmentIds, fn ($id) => is_numeric($id)));
        if (empty($deptIds)) {
            return collect();
        }

        $leadKeywords = (array) __('performance.supervisors.lead_keywords');
        $likeCases = [];
        $bindings = [];
        foreach ($leadKeywords as $kw) {
            $likeCases[] = 'job_titles.name LIKE ?';
            $bindings[] = '%'.$kw.'%';
        }
        $rawOrder = ! empty($likeCases) ? 'CASE WHEN ('.implode(' OR ', $likeCases).') THEN 0 ELSE 1 END' : '1';

        return Employee::query()
            ->where('employees.company_id', $companyId)
            ->whereIn('employees.department_id', $deptIds)
            ->join('job_titles', 'employees.job_title_id', '=', 'job_titles.id')
            ->join('users', 'employees.user_id', '=', 'users.id')
            ->orderByRaw($rawOrder, $bindings)
            ->orderBy('job_titles.sort_order')
            ->select('employees.department_id', 'users.name as name', 'job_titles.name as title')
            ->get()
            ->unique('department_id')
            ->keyBy('department_id');
    }

    /**
     * Level 3: Employees Summary for a single department in a company.
     *
     * @return Collection<int, object>
     */
    public static function getEmployeesSummary(int $companyId, int|string|null $departmentId, array $filters = []): Collection
    {
        $user = auth()->user();
        $authorizedCompanyIds = self::getAuthorizedCompanyIds($user);

        if (! in_array($companyId, $authorizedCompanyIds, true)) {
            return collect();
        }

        [$startDate, $endDate] = self::resolveDateRange($filters);
        $branchId = self::resolveBranchId($filters, $user);
        $teamIds = self::isSupervisorViewer($user) ? self::supervisorTeamIds($user, $startDate, $endDate) : null;

        $query = Employee::query()
            ->nonTemporary()
            ->where('employees.company_id', $companyId)
            ->with(['user', 'jobTitle', 'branch']);

        if ($departmentId === 'unassigned' || $departmentId === 0 || $departmentId === null) {
            $query->whereNull('employees.department_id');
        } else {
            $query->where('employees.department_id', (int) $departmentId);
        }

        if ($branchId !== null) {
            $query->where('employees.branch_id', $branchId);
        }

        if ($teamIds !== null) {
            $query->whereIn('employees.id', $teamIds);
        }

        if (filled($filters['search'] ?? null)) {
            $term = trim($filters['search']);
            $query->where(function (Builder $q) use ($term) {
                $q->where('employees.employee_number', 'like', "%{$term}%")
                    ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', "%{$term}%"));
            });
        }

        $employees = $query->orderBy('employees.id')->get();
        if ($employees->isEmpty()) {
            return collect();
        }

        $empIds = $employees->pluck('id')->all();

        // 1. Services by executed_by
        $servicesMap = self::selectServiceMetrics(
            self::servicesAggregateQuery([$companyId], $branchId, $teamIds)
                ->whereIn('service_transactions.executed_by', $empIds)
                ->selectRaw('service_transactions.executed_by as employee_id'),
            $startDate,
            $endDate,
        )->groupBy('service_transactions.executed_by')
            ->get()
            ->keyBy('employee_id');

        // 2. Deficiencies by responsible_employee_id
        $deficienciesMap = self::deficienciesAggregateQuery([$companyId], $branchId, $startDate, $endDate, $teamIds)
            ->whereIn('unified_deficiency_tasks.responsible_employee_id', $empIds)
            ->selectRaw('
                unified_deficiency_tasks.responsible_employee_id as employee_id,
                COUNT(*) as open_deficiencies
            ')
            ->groupBy('unified_deficiency_tasks.responsible_employee_id')
            ->get()
            ->keyBy('employee_id');

        // 3. Tasks by task_assignments.employee_id
        $tasksMap = self::selectTaskMetrics(
            self::tasksAggregateQuery([$companyId], $branchId, $teamIds)
                ->whereIn('task_assignments.employee_id', $empIds)
                ->selectRaw('task_assignments.employee_id'),
            $startDate,
            $endDate,
        )->groupBy('task_assignments.employee_id')
            ->get()
            ->keyBy('employee_id');

        $saudiIds = Employee::query()
            ->whereIn('id', $empIds)
            ->saudi()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $saudiSet = array_flip($saudiIds);
        $employees->reject(fn (Employee $employee) => isset($saudiSet[$employee->id]))->loadMissing('details');

        return $employees->map(function (Employee $employee) use ($servicesMap, $deficienciesMap, $tasksMap, $saudiSet) {
            $eid = $employee->id;
            $srv = $servicesMap->get($eid);
            $def = $deficienciesMap->get($eid);
            $tsk = $tasksMap->get($eid);

            $isSaudi = isset($saudiSet[$eid]) || $employee->isSaudi();

            $performanceUrl = null;
            try {
                if ($isSaudi) {
                    $performanceUrl = SaudiEmployeeResource::getUrl('performance', ['record' => $employee]);
                } else {
                    $performanceUrl = ForeignEmployeeResource::getUrl('performance', ['record' => $employee]);
                }
            } catch (\Throwable $e) {
                $performanceUrl = null;
            }

            return (object) [
                'employee_id' => $eid,
                'name' => $employee->user?->name ?? __('performance.fallback.employee', ['id' => $eid]),
                'employee_number' => $employee->employee_number,
                'job_title' => $employee->jobTitle?->name ?? '—',
                'branch_name' => $employee->branch?->name ?? '—',
                'is_saudi' => $isSaudi,
                'performance_url' => $performanceUrl,
                ...self::metrics($srv, $def, $tsk, $isSaudi),
            ];
        });
    }

    /**
     * Level 4: Kashf Services Query for Modal with pagination.
     */
    public static function getKashfServicesQuery(
        array $filters,
        string $type = 'all',
        ?int $companyId = null,
        int|string|null $departmentId = null,
        ?int $employeeId = null
    ): Builder {
        $user = auth()->user();
        $targetCompanyIds = self::kashfCompanyIds($filters, $companyId, $user);
        [$startDate, $endDate] = self::resolveDateRange($filters);
        $branchId = self::resolveBranchId($filters, $user);
        $teamIds = self::isSupervisorViewer($user) ? self::supervisorTeamIds($user, $startDate, $endDate) : null;

        $query = self::servicesAggregateQuery($targetCompanyIds, $branchId, $teamIds)
            ->with(['service', 'executor.user', 'employee.user', 'company']);

        if ($employeeId) {
            $query->where('service_transactions.executed_by', $employeeId);
        } elseif ($departmentId === 'unassigned' || $departmentId === 0) {
            $query->where(function (Builder $q) {
                $q->whereNull('service_transactions.executed_by')
                    ->orWhereHas('executor', fn (Builder $e) => $e->whereNull('employees.department_id'));
            });
        } elseif ($departmentId) {
            $query->whereHas('executor', fn (Builder $e) => $e->where('employees.department_id', (int) $departmentId));
        }

        match ($type) {
            'active' => $query->where('service_transactions.service_status', 'active')
                ->whereBetween('service_transactions.transaction_date', [$startDate, $endDate]),
            'completed' => $query->where('service_transactions.service_status', 'completed')
                ->whereBetween('service_transactions.completion_date', [$startDate, $endDate]),
            'overdue' => $query->where('service_transactions.service_status', 'active')
                ->whereBetween('service_transactions.due_date', [$startDate, $endDate])
                ->where('service_transactions.due_date', '<', today()->toDateString()),
            'on_time' => $query->where('service_transactions.service_status', 'completed')
                ->whereBetween('service_transactions.completion_date', [$startDate, $endDate])
                ->whereNotNull('service_transactions.due_date')
                ->whereColumn('service_transactions.completion_date', '<=', 'service_transactions.due_date'),
            default => $query->where(function (Builder $q) use ($startDate, $endDate) {
                $q->whereBetween('service_transactions.transaction_date', [$startDate, $endDate])
                    ->orWhereBetween('service_transactions.completion_date', [$startDate, $endDate])
                    ->orWhereBetween('service_transactions.due_date', [$startDate, $endDate]);
            }),
        };

        return $query->orderByDesc('service_transactions.id');
    }

    /**
     * Level 4: Kashf Deficiencies Query for Modal with pagination.
     */
    public static function getKashfDeficienciesQuery(
        array $filters,
        ?int $companyId = null,
        int|string|null $departmentId = null,
        ?int $employeeId = null
    ): Builder {
        $user = auth()->user();
        $targetCompanyIds = self::kashfCompanyIds($filters, $companyId, $user);
        [$startDate, $endDate] = self::resolveDateRange($filters);
        $branchId = self::resolveBranchId($filters, $user);
        $teamIds = self::isSupervisorViewer($user) ? self::supervisorTeamIds($user, $startDate, $endDate) : null;

        $query = self::deficienciesAggregateQuery($targetCompanyIds, $branchId, $startDate, $endDate, $teamIds)
            ->with(['responsibleEmployee.user', 'transaction.service', 'company']);

        if ($employeeId) {
            $query->where('unified_deficiency_tasks.responsible_employee_id', $employeeId);
        } elseif ($departmentId === 'unassigned' || $departmentId === 0) {
            $query->where(function (Builder $q) {
                $q->whereNull('unified_deficiency_tasks.responsible_employee_id')
                    ->orWhereHas('responsibleEmployee', fn (Builder $e) => $e->whereNull('employees.department_id'));
            });
        } elseif ($departmentId) {
            $query->whereHas('responsibleEmployee', fn (Builder $e) => $e->where('employees.department_id', (int) $departmentId));
        }

        return $query->orderByDesc('unified_deficiency_tasks.id');
    }

    /**
     * Level 4: Kashf Tasks Query for Modal with pagination.
     */
    public static function getKashfTasksQuery(
        array $filters,
        string $type = 'all',
        ?int $companyId = null,
        int|string|null $departmentId = null,
        ?int $employeeId = null
    ): Builder {
        $user = auth()->user();
        $targetCompanyIds = self::kashfCompanyIds($filters, $companyId, $user);
        [$startDate, $endDate] = self::resolveDateRange($filters);
        $branchId = self::resolveBranchId($filters, $user);
        $teamIds = self::isSupervisorViewer($user) ? self::supervisorTeamIds($user, $startDate, $endDate) : null;

        $query = self::tasksAggregateQuery($targetCompanyIds, $branchId, $teamIds)
            ->with(['task', 'taskAssignment.employee.user'])
            ->select('task_assignment_items.*');

        if ($employeeId) {
            $query->where('task_assignments.employee_id', $employeeId);
        } elseif ($departmentId === 'unassigned' || $departmentId === 0) {
            $query->whereNull('employees.department_id');
        } elseif ($departmentId) {
            $query->where('employees.department_id', (int) $departmentId);
        }

        match ($type) {
            'completed' => $query->where('task_assignment_items.status', TaskStatus::COMPLETED->value)
                ->whereBetween('task_assignment_items.completed_at', [$startDate, $endDate]),
            'due' => $query->whereBetween('task_assignment_items.due_date', [$startDate, $endDate]),
            'overdue' => $query->whereIn('task_assignment_items.status', [TaskStatus::PENDING->value, TaskStatus::IN_PROGRESS->value])
                ->whereNotNull('task_assignment_items.due_date')
                ->where('task_assignment_items.due_date', '<', today()->toDateString()),
            'postponed' => $query->where('task_assignment_items.status', TaskStatus::POSTPONED->value),
            default => $query->where(function (Builder $q) use ($startDate, $endDate) {
                $q->whereBetween('task_assignment_items.due_date', [$startDate, $endDate])
                    ->orWhereBetween('task_assignment_items.completed_at', [$startDate, $endDate]);
            }),
        };

        return $query->orderByDesc('task_assignment_items.id');
    }

    private static function kashfCompanyIds(array $filters, ?int $companyId, ?User $user): array
    {
        $ids = self::getAuthorizedCompanyIds($user);

        if (filled($filters['company_id'] ?? null)) {
            $ids = array_intersect($ids, (array) $filters['company_id']);
        }

        return $companyId === null ? $ids : array_intersect($ids, [$companyId]);
    }

    /**
     * Base query for services aggregation with filters and scopes applied.
     * A null team leaves the company and branch scope intact. An empty team matches no records.
     */
    private static function servicesAggregateQuery(
        array $companyIds,
        ?int $branchId,
        ?array $teamIds
    ): Builder {
        $query = ServiceTransaction::query()
            ->whereIn('service_transactions.company_id', $companyIds)
            ->where(function (Builder $status) {
                $status->whereNull('service_transactions.metadata->status')
                    ->orWhere('service_transactions.metadata->status', '!=', 'cancelled');
            });

        if ($branchId !== null) {
            $query->whereHas('employee', function (Builder $emp) use ($branchId) {
                $emp->where('employees.branch_id', $branchId);
            });
        }

        if ($teamIds !== null) {
            $query->whereIn('service_transactions.executed_by', $teamIds);
        }

        return $query;
    }

    /**
     * Base query for deficiencies aggregation with filters and scopes applied.
     */
    private static function deficienciesAggregateQuery(
        array $companyIds,
        ?int $branchId,
        string $startDate,
        string $endDate,
        ?array $teamIds
    ): Builder {
        $query = UnifiedDeficiencyTask::query()
            ->whereIn('unified_deficiency_tasks.company_id', $companyIds)
            ->where('unified_deficiency_tasks.status', 'open')
            ->whereBetween('unified_deficiency_tasks.created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay(),
            ])
            ->where(function (Builder $morph) {
                $morph->whereNotNull('unified_deficiency_tasks.service_transaction_id')
                    ->orWhere('unified_deficiency_tasks.taskable_type', ServiceTransaction::class);
            });

        if ($branchId !== null) {
            $query->where(function (Builder $b) use ($branchId) {
                $b->whereHas('responsibleEmployee', fn (Builder $e) => $e->where('employees.branch_id', $branchId))
                    ->orWhereHas('transaction.employee', fn (Builder $e) => $e->where('employees.branch_id', $branchId));
            });
        }

        if ($teamIds !== null) {
            $query->whereIn('unified_deficiency_tasks.responsible_employee_id', $teamIds);
        }

        return $query;
    }

    /**
     * Base query for task items aggregation with filters and scopes applied.
     */
    private static function tasksAggregateQuery(
        array $companyIds,
        ?int $branchId,
        ?array $teamIds
    ): Builder {
        $saudiIds = Employee::query()->saudi()->select('employees.id');

        $query = TaskAssignmentItem::query()
            ->join('task_assignments', 'task_assignment_items.task_assignment_id', '=', 'task_assignments.id')
            ->join('employees', 'task_assignments.employee_id', '=', 'employees.id')
            ->whereIn('employees.company_id', $companyIds)
            ->whereIn('task_assignments.employee_id', $saudiIds);

        if ($branchId !== null) {
            $query->where('employees.branch_id', $branchId);
        }

        if ($teamIds !== null) {
            $query->whereIn('task_assignments.employee_id', $teamIds);
        }

        return $query;
    }

    private static function metrics(?object $services, ?object $deficiencies, ?object $tasks, bool $includeTasks = true): array
    {
        $tasksDue = (int) ($tasks->task_due_in_period ?? 0);

        return [
            'active_services' => (int) ($services->active_services ?? 0),
            'completed_services' => (int) ($services->completed_services ?? 0),
            'overdue_services' => (int) ($services->overdue_services ?? 0),
            'on_time_rate' => self::percentage((int) ($services->on_time_completed ?? 0), (int) ($services->dated_completed ?? 0)),
            'open_deficiencies' => (int) ($deficiencies->open_deficiencies ?? 0),
            'task_due_in_period' => $includeTasks ? $tasksDue : null,
            'task_completed_in_period' => $includeTasks ? (int) ($tasks->task_completed_in_period ?? 0) : null,
            'task_overdue' => $includeTasks ? (int) ($tasks->task_overdue ?? 0) : null,
            'task_completion_rate' => $includeTasks ? self::percentage((int) ($tasks->task_due_completed ?? 0), $tasksDue) : null,
        ];
    }

    private static function percentage(int $numerator, int $denominator): ?float
    {
        return $denominator > 0 ? round(($numerator / $denominator) * 100, 1) : null;
    }

    private static function selectServiceMetrics(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->selectRaw('
            COUNT(CASE WHEN service_transactions.service_status = "active" AND service_transactions.transaction_date BETWEEN ? AND ? THEN 1 END) as active_services,
            COUNT(CASE WHEN service_transactions.service_status = "completed" AND service_transactions.completion_date BETWEEN ? AND ? THEN 1 END) as completed_services,
            COUNT(CASE WHEN service_transactions.service_status = "active" AND service_transactions.due_date BETWEEN ? AND ? AND service_transactions.due_date < ? THEN 1 END) as overdue_services,
            COUNT(CASE WHEN service_transactions.service_status = "completed" AND service_transactions.completion_date BETWEEN ? AND ? AND service_transactions.due_date IS NOT NULL AND service_transactions.completion_date <= service_transactions.due_date THEN 1 END) as on_time_completed,
            COUNT(CASE WHEN service_transactions.service_status = "completed" AND service_transactions.completion_date BETWEEN ? AND ? AND service_transactions.due_date IS NOT NULL THEN 1 END) as dated_completed
        ', [
            $startDate, $endDate,
            $startDate, $endDate,
            $startDate, $endDate, today()->toDateString(),
            $startDate, $endDate,
            $startDate, $endDate,
        ]);
    }

    private static function selectTaskMetrics(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->selectRaw('
            SUM(CASE WHEN task_assignment_items.status = ? AND task_assignment_items.completed_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as task_completed_in_period,
            SUM(CASE WHEN task_assignment_items.due_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as task_due_in_period,
            SUM(CASE WHEN task_assignment_items.due_date BETWEEN ? AND ? AND task_assignment_items.status = ? AND task_assignment_items.completed_at IS NOT NULL AND task_assignment_items.completed_at <= ? THEN 1 ELSE 0 END) as task_due_completed,
            SUM(CASE WHEN task_assignment_items.status IN (?, ?) AND task_assignment_items.due_date IS NOT NULL AND task_assignment_items.due_date < ? THEN 1 ELSE 0 END) as task_overdue
        ', [
            TaskStatus::COMPLETED->value, $startDate, $endDate,
            $startDate, $endDate,
            $startDate, $endDate, TaskStatus::COMPLETED->value, $endDate,
            TaskStatus::PENDING->value, TaskStatus::IN_PROGRESS->value, today()->toDateString(),
        ]);
    }
}
