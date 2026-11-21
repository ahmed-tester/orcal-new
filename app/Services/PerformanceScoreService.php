<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Employee;
use App\Models\ServiceTransaction;
use App\Models\TaskAssignmentItem;
use App\Models\UnifiedDeficiencyTask;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Operational performance score (0-100) for the operations overview.
 *
 * Phase 1 sources per the handoff plan: overdue open task assignments
 * (weight 4) and overdue open unified deficiencies (weight 2). The score
 * measures the currently open overdue items at read time — old overdue
 * records that are still open are included, and closing them restores the
 * points on the next read. The score is never stored and never goes below
 * zero. Financial sources, expiries and escalation remain for later phases,
 * so the score is always reported as partial.
 */
class PerformanceScoreService
{
    public const FULL_SCORE = 100;

    /** Scores below this threshold call for a management review (escalation lands in a later phase). */
    public const REVIEW_THRESHOLD = 65;

    public const OVERDUE_TASK_WEIGHT = 4;

    public const OVERDUE_DEFICIENCY_WEIGHT = 2;

    /** Version of the calculation rules, stored on every escalation open snapshot. */
    public const RULES_VERSION = 'operational-v1';

    /**
     * Score for the requested scope: the whole authorized report scope, one
     * company, or one department within a company.
     *
     * @param  array<string, mixed>  $filters
     */
    public static function getScore(array $filters = [], ?int $companyId = null, int|string|null $departmentId = null): object
    {
        $user = auth()->user();
        $authorizedCompanyIds = CompanyDepartmentPerformanceService::getAuthorizedCompanyIds($user);

        if ($authorizedCompanyIds === []) {
            return (object) [
                'available' => false,
                'score' => null,
                'total_deduction' => 0,
                'is_partial' => true,
                'rules_version' => self::RULES_VERSION,
                'deductions' => collect(),
            ];
        }

        $scopedCompanyIds = filled($filters['company_id'] ?? null)
            ? array_values(array_intersect($authorizedCompanyIds, (array) $filters['company_id']))
            : $authorizedCompanyIds;

        if ($companyId !== null) {
            $scopedCompanyIds = array_values(array_intersect($scopedCompanyIds, [$companyId]));
        }

        if ($scopedCompanyIds === []) {
            return (object) [
                'available' => false,
                'score' => null,
                'total_deduction' => 0,
                'is_partial' => true,
                'rules_version' => self::RULES_VERSION,
                'deductions' => collect(),
            ];
        }

        $branchId = CompanyDepartmentPerformanceService::resolveBranchId($filters, $user);
        $teamIds = CompanyDepartmentPerformanceService::isSupervisorViewer($user)
            ? CompanyDepartmentPerformanceService::supervisorTeamIds(
                $user,
                ...CompanyDepartmentPerformanceService::resolveDateRange($filters)
            )
            : null;

        $taskRows = self::overdueTasksByScope($scopedCompanyIds, $branchId, $teamIds);
        $deficiencyRows = self::overdueDeficienciesByScope($scopedCompanyIds, $branchId, $teamIds);

        $taskCount = self::scopeCount($taskRows, $companyId, $departmentId);
        $deficiencyCount = self::scopeCount($deficiencyRows, $companyId, $departmentId);

        return self::scoreFromCounts($taskCount, $deficiencyCount);
    }

    /**
     * Build the score object from the aggregated overdue counts.
     */
    public static function scoreFromCounts(int $taskCount, int $deficiencyCount): object
    {
        $deductions = collect([
            (object) [
                'key' => 'overdue_tasks',
                'count' => $taskCount,
                'weight' => self::OVERDUE_TASK_WEIGHT,
                'points' => $taskCount * self::OVERDUE_TASK_WEIGHT,
            ],
            (object) [
                'key' => 'overdue_deficiencies',
                'count' => $deficiencyCount,
                'weight' => self::OVERDUE_DEFICIENCY_WEIGHT,
                'points' => $deficiencyCount * self::OVERDUE_DEFICIENCY_WEIGHT,
            ],
        ]);

        $totalDeduction = (int) $deductions->sum('points');

        return (object) [
            'available' => true,
            'score' => max(0, self::FULL_SCORE - $totalDeduction),
            'total_deduction' => $totalDeduction,
            'is_partial' => true,
            'rules_version' => self::RULES_VERSION,
            'deductions' => $deductions,
        ];
    }

    /**
     * Build the comprehensive operational performance score for an employee.
     */
    public static function employeeOperationalScore(
        ?float $onTimeRate,
        ?float $taskCompletionRate,
        int $overdueServices,
        int $openDeficiencies,
        bool $hasActivity
    ): object {
        if (! $hasActivity) {
            return (object) [
                'available' => false,
                'score' => null,
                'is_rankable' => false,
                'base_rate' => null,
                'total_deduction' => 0,
                'deductions' => collect(),
            ];
        }

        $baseRate = null;
        if ($onTimeRate !== null && $taskCompletionRate !== null) {
            $baseRate = ($onTimeRate * 0.5) + ($taskCompletionRate * 0.5);
        } elseif ($onTimeRate !== null) {
            $baseRate = $onTimeRate;
        } elseif ($taskCompletionRate !== null) {
            $baseRate = $taskCompletionRate;
        } else {
            $baseRate = 100.0;
        }

        $deductions = collect([
            (object) [
                'key' => 'overdue_services',
                'count' => $overdueServices,
                'weight' => self::OVERDUE_TASK_WEIGHT,
                'points' => $overdueServices * self::OVERDUE_TASK_WEIGHT,
            ],
            (object) [
                'key' => 'open_deficiencies',
                'count' => $openDeficiencies,
                'weight' => self::OVERDUE_DEFICIENCY_WEIGHT,
                'points' => $openDeficiencies * self::OVERDUE_DEFICIENCY_WEIGHT,
            ],
        ]);

        $totalDeduction = (int) $deductions->sum('points');
        $score = (int) round(max(0, min(self::FULL_SCORE, $baseRate - $totalDeduction)));

        return (object) [
            'available' => true,
            'score' => $score,
            'is_rankable' => true,
            'base_rate' => round($baseRate, 1),
            'total_deduction' => $totalDeduction,
            'deductions' => $deductions,
        ];
    }

    /**
     * Currently overdue open task items grouped by company and assignee
     * department. Mirrors the task scoping of the performance page: company,
     * branch, supervisor team and the Saudi-employee-only assignment scope.
     *
     * @return Collection<int, object>
     */
    public static function overdueTasksByScope(array $companyIds, ?int $branchId, ?array $teamIds): Collection
    {
        return self::overdueTaskQuery($companyIds, $branchId, $teamIds)
            ->selectRaw('employees.company_id, COALESCE(employees.department_id, 0) as department_id, COUNT(*) as count')
            ->groupBy('employees.company_id', DB::raw('COALESCE(employees.department_id, 0)'))
            ->get();
    }

    /**
     * Currently overdue open deficiencies grouped by company and responsible
     * employee department, using the same service-linked restriction as the
     * performance page's open-deficiencies metric.
     *
     * @return Collection<int, object>
     */
    public static function overdueDeficienciesByScope(array $companyIds, ?int $branchId, ?array $teamIds): Collection
    {
        return self::overdueDeficiencyQuery($companyIds, $branchId, $teamIds)
            ->leftJoin('employees as resp_emp', 'unified_deficiency_tasks.responsible_employee_id', '=', 'resp_emp.id')
            ->selectRaw('unified_deficiency_tasks.company_id, COALESCE(resp_emp.department_id, 0) as department_id, COUNT(*) as count')
            ->groupBy('unified_deficiency_tasks.company_id', DB::raw('COALESCE(resp_emp.department_id, 0)'))
            ->get();
    }

    /**
     * Count of overdue open records inside the requested scope. The
     * aggregated queries use 0 as the department bucket for records without
     * a department, which maps to the "unassigned" pseudo department.
     */
    private static function scopeCount(Collection $rows, ?int $companyId, int|string|null $departmentId): int
    {
        $rows = $companyId !== null
            ? $rows->where('company_id', $companyId)
            : $rows;

        if ($departmentId !== null) {
            $bucket = $departmentId === 'unassigned' ? 0 : (int) $departmentId;
            $rows = $rows->where('department_id', $bucket);
        }

        return (int) $rows->sum('count');
    }

    /**
     * Base query of overdue open task items. Deduction starts the day after
     * the due date, so items due today or later are excluded, and postponed
     * or completed items never deduct.
     */
    private static function overdueTaskQuery(array $companyIds, ?int $branchId, ?array $teamIds): Builder
    {
        $query = TaskAssignmentItem::query()
            ->join('task_assignments', 'task_assignment_items.task_assignment_id', '=', 'task_assignments.id')
            ->join('employees', 'task_assignments.employee_id', '=', 'employees.id')
            ->whereIn('employees.company_id', $companyIds)
            ->whereIn('task_assignments.employee_id', Employee::query()->saudi()->select('employees.id'))
            ->whereIn('task_assignment_items.status', TaskStatus::employeeOpenValues())
            ->whereNotNull('task_assignment_items.due_date')
            ->where('task_assignment_items.due_date', '<', Carbon::today()->toDateString());

        if ($branchId !== null) {
            $query->where('employees.branch_id', $branchId);
        }

        if ($teamIds !== null) {
            $query->whereIn('task_assignments.employee_id', $teamIds);
        }

        return $query;
    }

    /**
     * Base query of overdue open unified deficiencies, mirroring the
     * deficiency scoping of the performance page.
     */
    private static function overdueDeficiencyQuery(array $companyIds, ?int $branchId, ?array $teamIds): Builder
    {
        $query = UnifiedDeficiencyTask::query()
            ->whereIn('unified_deficiency_tasks.company_id', $companyIds)
            ->where('unified_deficiency_tasks.status', 'open')
            ->whereNotNull('unified_deficiency_tasks.due_date')
            ->where('unified_deficiency_tasks.due_date', '<', Carbon::today()->toDateString())
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
}
