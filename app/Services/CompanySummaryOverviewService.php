<?php

namespace App\Services;

use App\Filament\Resources\Violations\Pages\ViolationsSummary;
use App\Models\Company;
use App\Models\Debt;
use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Models\ServiceTransaction;
use App\Models\TaskAssignment;
use Illuminate\Support\Facades\Gate;

class CompanySummaryOverviewService
{
    /**
     * Build the simplified executive summary payload for either the general scope
     * or a specific selected department.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int>  $authorizedCompanies
     * @return array<string, mixed>
     */
    public static function build(
        array $filters,
        array $authorizedCompanies,
        int|string|null $departmentId = null
    ): array {
        $isSupervisor = CompanyDepartmentPerformanceService::isSupervisorViewer();
        $isDepartmentScoped = $departmentId !== null || $isSupervisor;

        $canTasks = Gate::allows('viewAny', TaskAssignment::class);
        $canServices = Gate::allows('viewAny', ServiceTransaction::class);
        $canDeficiencies = $canServices;
        $canOperationalScore = $canTasks && $canDeficiencies;

        $stats = ($canTasks || $canServices)
            ? CompanyDepartmentPerformanceService::getOverallStats($filters, null, $departmentId)
            : (object) ['employees_count' => 0, 'task_completion_rate' => null, 'on_time_rate' => null];
        $score = ($canTasks || $canDeficiencies)
            ? PerformanceScoreService::getScore($filters, null, $departmentId, $canTasks, $canDeficiencies)
            : (object) ['available' => false, 'score' => null, 'total_deduction' => 0, 'deductions' => collect()];
        $hasOperationalActivity = $canOperationalScore && self::hasOperationalActivity($stats, $score);

        $canPayroll = Gate::allows('viewAny', PayrollRecord::class);
        $canDebts = ! $isSupervisor && Gate::allows('viewAny', Debt::class);
        $canViolations = ! $isSupervisor && ViolationsSummary::canAccess();
        $canEmployeeCompliance = Gate::allows('viewAny', Employee::class);
        $canCompanyCompliance = ! $isSupervisor && Gate::allows('viewAny', Company::class);

        $financial = ($canPayroll || (! $isDepartmentScoped && $canDebts))
            ? CompanyFinancialPerformanceService::overview($filters, $authorizedCompanies, null, $departmentId, false)
            : ['treasury' => null, 'groups' => []];

        $companyDebts = null;
        if ($isDepartmentScoped && $departmentId !== null && $canDebts) {
            $companyDebts = CompanyFinancialPerformanceService::debtsOverview($filters, $authorizedCompanies);
        }

        $violations = $canViolations ? CompanyViolationsPerformanceService::getStats($filters) : null;
        $compliance = ($canEmployeeCompliance || $canCompanyCompliance)
            ? CompanyCompliancePerformanceService::getStats($filters, null, $departmentId)
            : null;

        $companyCompliance = null;
        if ($departmentId !== null && $canCompanyCompliance) {
            $companyComplianceStats = CompanyCompliancePerformanceService::getStats($filters);
            $companyCompliance = [
                'licenses' => $companyComplianceStats['licenses'] ?? null,
                'subscriptions' => $companyComplianceStats['subscriptions'] ?? null,
            ];
        }

        return [
            'is_supervisor' => $isSupervisor,
            'is_department_view' => $departmentId !== null,
            'stats' => $stats,
            'score' => $score,
            'has_operational_activity' => $hasOperationalActivity,
            'cards' => [
                'tasks_deficiencies' => ($canTasks || $canDeficiencies)
                    ? self::tasksDeficienciesCard($stats, $score, $departmentId, $canTasks, $canDeficiencies)
                    : null,
                'collection_debts' => self::collectionDebtsCard(
                    $financial['groups']['collection'] ?? null,
                    ! $isDepartmentScoped ? ($financial['groups']['debts'] ?? null) : null,
                    $canPayroll,
                    ! $isDepartmentScoped && $canDebts,
                    $departmentId,
                ),
                'violations' => ! $isDepartmentScoped && $violations !== null
                    ? self::violationsCard($violations)
                    : null,
                'payroll' => $canPayroll
                    ? self::payrollCard($financial['groups']['payroll'] ?? null, $departmentId)
                    : null,
                'compliance' => $compliance !== null
                    ? self::complianceCard($compliance, $canEmployeeCompliance, ! $isDepartmentScoped && $canCompanyCompliance, $departmentId)
                    : null,
                'on_time' => $canServices
                    ? self::onTimeCard($stats, $departmentId)
                    : null,
            ],
            'company_level' => $departmentId !== null ? [
                'debts' => $canDebts && $companyDebts !== null ? self::debtsBlock($companyDebts) : null,
                'violations' => $violations !== null ? self::violationsCard($violations) : null,
                'compliance' => $companyCompliance !== null ? self::companyComplianceBlock($companyCompliance) : null,
            ] : null,
        ];
    }

    private static function hasOperationalActivity(object $stats, object $score): bool
    {
        return ($stats->active_services ?? 0) > 0
            || ($stats->completed_services ?? 0) > 0
            || ($stats->overdue_services ?? 0) > 0
            || ($stats->open_deficiencies ?? 0) > 0
            || ($stats->task_due_in_period ?? 0) > 0
            || ($stats->task_completed_in_period ?? 0) > 0
            || ($score->total_deduction ?? 0) > 0;
    }

    private static function tasksDeficienciesCard(
        object $stats,
        object $score,
        int|string|null $departmentId,
        bool $canTasks = true,
        bool $canDeficiencies = true
    ): array {
        $overdueDeficiencies = (int) ($score->deductions?->firstWhere('key', 'overdue_deficiencies')?->count ?? 0);
        $overdueTasks = (int) ($score->deductions?->firstWhere('key', 'overdue_tasks')?->count ?? 0);
        $deptArg = self::jsArg($departmentId);

        return [
            'can_tasks' => $canTasks,
            'can_deficiencies' => $canDeficiencies,
            'rate' => $canTasks ? $stats->task_completion_rate : null,
            'action' => $canTasks ? "openStatement('tasks', 'completed', null, {$deptArg})" : null,
            'task_due' => (int) ($stats->task_due_in_period ?? 0),
            'task_completed' => (int) ($stats->task_completed_in_period ?? 0),
            'task_overdue' => $overdueTasks,
            'open_deficiencies' => (int) ($stats->open_deficiencies ?? 0),
            'overdue_deficiencies' => $overdueDeficiencies,
            'actions' => [
                'due' => "openStatement('tasks', 'due', null, {$deptArg})",
                'completed' => "openStatement('tasks', 'completed', null, {$deptArg})",
                'tasks_overdue' => "openStatement('tasks', 'score_overdue', null, {$deptArg})",
                'deficiencies_open' => "openStatement('deficiencies', 'all', null, {$deptArg})",
                'deficiencies_overdue' => "openStatement('deficiencies', 'score_overdue', null, {$deptArg})",
            ],
        ];
    }

    private static function collectionDebtsCard(
        ?array $collection,
        ?array $debts,
        bool $canCollection,
        bool $canDebts,
        int|string|null $departmentId
    ): ?array {
        if (! $canCollection && ! $canDebts) {
            return null;
        }

        $deptArg = self::jsArg($departmentId);
        $collectionBlock = null;
        if ($canCollection && $collection !== null) {
            $rates = CompanyFinancialPerformanceService::indicatorRates('collection', $collection);
            $collectionBlock = [
                'rate' => $rates['collection_paid'] ?? null,
                'net' => (float) ($collection['collection_net'] ?? 0),
                'paid' => (float) ($collection['collection_paid'] ?? 0),
                'remaining' => (float) ($collection['collection_remaining'] ?? 0),
                'action' => "openStatement('financial', 'collection_remaining', null, {$deptArg})",
            ];
        }

        return [
            'collection' => $collectionBlock,
            'debts' => $canDebts && $debts !== null ? self::debtsBlock($debts) : null,
        ];
    }

    private static function debtsBlock(array $debts): array
    {
        $rates = CompanyFinancialPerformanceService::indicatorRates('debts', $debts);

        return [
            'rate' => $rates['debt_paid'] ?? null,
            'total' => (float) ($debts['debt_total'] ?? 0),
            'paid' => (float) ($debts['debt_paid'] ?? 0),
            'remaining' => (float) ($debts['debt_remaining'] ?? 0),
            'overdue' => (float) ($debts['debt_overdue'] ?? 0),
            'action' => "openStatement('financial', 'debt_remaining')",
            'overdue_action' => "openStatement('financial', 'debt_overdue')",
        ];
    }

    private static function violationsCard(array $violations): array
    {
        $totals = $violations['totals'] ?? [];
        $count = (int) ($totals['count'] ?? 0);
        $fullyPaid = (int) ($totals['fully_paid_count'] ?? 0);

        return [
            'rate' => $count > 0 ? round(($fullyPaid / $count) * 100, 1) : null,
            'count' => $count,
            'fully_paid_count' => $fullyPaid,
            'amount' => (float) ($totals['amount'] ?? 0),
            'paid' => (float) ($totals['paid'] ?? 0),
            'remaining' => (float) ($totals['remaining'] ?? 0),
            'action' => "openStatement('violations', 'all')",
            'paid_action' => "openStatement('violations', 'paid')",
            'remaining_action' => "openStatement('violations', 'unpaid')",
        ];
    }

    private static function payrollCard(?array $payroll, int|string|null $departmentId): ?array
    {
        if ($payroll === null) {
            return null;
        }

        $rates = CompanyFinancialPerformanceService::indicatorRates('payroll', $payroll);
        $deptArg = self::jsArg($departmentId);

        return [
            'rate' => $rates['payroll_paid'] ?? null,
            'net' => (float) ($payroll['payroll_net'] ?? 0),
            'paid' => (float) ($payroll['payroll_paid'] ?? 0),
            'remaining' => (float) ($payroll['payroll_remaining'] ?? 0),
            'action' => "openStatement('financial', 'payroll_remaining', null, {$deptArg})",
            'paid_action' => "openStatement('financial', 'payroll_paid', null, {$deptArg})",
        ];
    }

    private static function complianceCard(
        array $compliance,
        bool $canEmployee,
        bool $canCompany,
        int|string|null $departmentId
    ): ?array {
        if (! $canEmployee && ! $canCompany) {
            return null;
        }

        $deptArg = self::jsArg($departmentId);
        $documents = [];

        if ($canEmployee) {
            if (is_array($compliance['iqama'] ?? null)) {
                $total = (int) ($compliance['iqama']['total_tracked'] ?? 0);
                $expired = (int) ($compliance['iqama']['expired'] ?? 0);
                $soon = (int) ($compliance['iqama']['within_3m'] ?? 0);
                $documents['iqama'] = [
                    'rate' => $total > 0 ? round((($total - $expired) / $total) * 100, 1) : null,
                    'tracked' => $total,
                    'expired' => $expired,
                    'expiring_soon' => $soon,
                    'action' => "openStatement('compliance', 'iqama_expired', null, {$deptArg})",
                    'soon_action' => "openStatement('compliance', 'iqama_within_3m', null, {$deptArg})",
                ];
            }

            foreach (['work_permits' => 'work_permit', 'insurance' => 'insurance'] as $key => $prefix) {
                $bucket = $compliance[$key] ?? null;
                if (! is_array($bucket)) {
                    continue;
                }
                $expired = (int) ($bucket['expired'] ?? 0);
                $soon = (int) ($bucket['expiring_soon'] ?? 0);
                $valid = (int) ($bucket['valid'] ?? 0);
                $total = $expired + $soon + $valid;
                $documents[$key] = [
                    'rate' => $total > 0 ? round((($total - $expired) / $total) * 100, 1) : null,
                    'tracked' => $total,
                    'expired' => $expired,
                    'expiring_soon' => $soon,
                    'action' => "openStatement('compliance', '{$prefix}_expired', null, {$deptArg})",
                    'soon_action' => "openStatement('compliance', '{$prefix}_expiring_soon', null, {$deptArg})",
                ];
            }
        }

        $companyBlock = $canCompany ? self::companyComplianceBlock($compliance) : null;

        if ($documents === [] && $companyBlock === null) {
            return null;
        }

        return [
            'documents' => $documents,
            'company' => $companyBlock,
        ];
    }

    private static function companyComplianceBlock(array $compliance): ?array
    {
        $licenses = $compliance['licenses'] ?? null;
        $subscriptions = $compliance['subscriptions'] ?? null;

        if ($licenses === null && $subscriptions === null) {
            return null;
        }

        $licenseRows = [];
        foreach (['municipality', 'civil_defense', 'commercial_reg'] as $license) {
            $row = $licenses[$license] ?? null;
            if (! is_array($row)) {
                continue;
            }
            $expired = (int) ($row['expired'] ?? 0);
            $soon = (int) ($row['expiring_soon'] ?? 0);
            $valid = (int) ($row['valid'] ?? 0);
            $tracked = $expired + $soon + $valid;
            $licenseRows[$license] = [
                'rate' => $tracked > 0 ? round((($tracked - $expired) / $tracked) * 100, 1) : null,
                'tracked' => $tracked,
                'expired' => $expired,
                'expiring_soon' => $soon,
                'action' => "openStatement('compliance', '{$license}_expired')",
                'soon_action' => "openStatement('compliance', '{$license}_expiring_soon')",
            ];
        }

        return [
            'licenses' => $licenseRows,
            'subscriptions' => $subscriptions,
        ];
    }

    private static function onTimeCard(object $stats, int|string|null $departmentId): array
    {
        $deptArg = self::jsArg($departmentId);

        return [
            'rate' => $stats->on_time_rate,
            'active_services' => (int) ($stats->active_services ?? 0),
            'completed_services' => (int) ($stats->completed_services ?? 0),
            'overdue_services' => (int) ($stats->overdue_services ?? 0),
            'action' => "openStatement('services', 'on_time', null, {$deptArg})",
            'active_action' => "openStatement('services', 'active', null, {$deptArg})",
            'completed_action' => "openStatement('services', 'completed', null, {$deptArg})",
            'overdue_action' => "openStatement('services', 'overdue', null, {$deptArg})",
        ];
    }

    private static function jsArg(int|string|null $departmentId): string
    {
        if ($departmentId === null) {
            return 'null';
        }

        return is_int($departmentId) ? (string) $departmentId : "'".addslashes((string) $departmentId)."'";
    }
}
