<?php

namespace App\Services;

use App\Filament\Resources\ServiceTransactions\ServiceTransactionResource;
use App\Models\Debt;
use App\Models\PayrollRecord;
use App\Models\TreasuryTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class CompanyFinancialPerformanceService
{
    public const TREASURY_METRICS = ['current_balance', 'incoming', 'outgoing', 'net_flow'];

    public const PAYROLL_METRICS = [
        'payroll_net' => 'net_amount',
        'payroll_paid' => 'paid_amount',
        'payroll_remaining' => 'remaining_amount',
        'collection_net' => 'net_amount',
        'collection_paid' => 'paid_amount',
        'collection_remaining' => 'remaining_amount',
    ];

    public const DEBT_METRICS = [
        'debt_total' => 'total_amount',
        'debt_paid' => 'paid_amount',
        'debt_remaining' => 'remaining_amount',
        'debt_overdue' => 'remaining_amount',
    ];

    public static function canView(): bool
    {
        return self::canViewTreasury() || self::canViewWorkflow()
            || Gate::allows('viewAny', PayrollRecord::class) || Gate::allows('viewAny', Debt::class);
    }

    public static function canViewTreasury(): bool
    {
        return Gate::allows('viewAny', TreasuryTransaction::class);
    }

    private static function canViewWorkflow(): bool
    {
        return ServiceTransactionResource::canViewAny();
    }

    public static function isTreasuryMetric(string $type): bool
    {
        return in_array($type, self::TREASURY_METRICS, true);
    }

    public static function authorizeStatement(string $type, array $filters): void
    {
        $allowed = match (true) {
            self::isTreasuryMetric($type) => self::canViewTreasury()
                && CompanyDepartmentPerformanceService::resolveBranchId($filters) === null,
            self::isWorkflowMetric($type) => self::canViewWorkflow(),
            isset(self::PAYROLL_METRICS[$type]) => Gate::allows('viewAny', PayrollRecord::class),
            isset(self::DEBT_METRICS[$type]) => Gate::allows('viewAny', Debt::class),
            default => abort(404),
        };

        abort_unless($allowed, 403);
    }

    public static function overview(
        array $filters,
        array $authorizedCompanies,
        ?int $companyId = null,
        int|string|null $departmentId = null,
        bool $includeCompanyWorkflowAndTreasury = true
    ): array {
        $isSupervisor = CompanyDepartmentPerformanceService::isSupervisorViewer();
        $isDepartmentScoped = $departmentId !== null || $isSupervisor;
        $treasury = ($includeCompanyWorkflowAndTreasury && ! $isDepartmentScoped && self::canViewTreasury())
            ? TreasurySummaryService::getPerformanceStats($filters, $authorizedCompanies)
            : null;
        [$filters, $companyIds] = self::scope($filters, $authorizedCompanies, $companyId);
        $groups = [];

        if ($includeCompanyWorkflowAndTreasury && ! $isDepartmentScoped && self::canViewWorkflow()) {
            $groups['receipts_and_custody'] = self::workflowTotals(ServiceFinancialPerformanceService::TRANSFER_METRICS, $filters, $companyIds);
            $groups['settlements'] = self::workflowTotals(ServiceFinancialPerformanceService::SETTLEMENT_METRICS, $filters, $companyIds);
        }

        if (Gate::allows('viewAny', PayrollRecord::class)) {
            $payrollQuery = PayrollSheetService::savedRecordsInPeriod($filters, $companyIds);
            self::applyPayrollEmployeeScope($payrollQuery, $filters, $departmentId);
            $rows = PayrollSheetService::selectSavedAmounts($payrollQuery)
                ->addSelect('operation_type')->groupBy('operation_type')->get()->keyBy('operation_type');

            foreach (['payroll', 'collection'] as $operation) {
                $row = $rows->get($operation);
                $groups[$operation] = [
                    $operation.'_net' => (float) ($row?->total_net ?? 0),
                    $operation.'_paid' => (float) ($row?->total_paid ?? 0),
                    $operation.'_remaining' => (float) ($row?->total_remaining ?? 0),
                ];
            }
        }

        if (! $isDepartmentScoped && Gate::allows('viewAny', Debt::class)) {
            $groups['debts'] = self::debtsOverview($filters, $authorizedCompanies, $companyId);
        }

        return ['treasury' => $treasury, 'groups' => $groups];
    }

    /**
     * Compute debt totals for the given company scope without querying unrelated financial groups.
     *
     * @return array<string, float>
     */
    public static function debtsOverview(array $filters, array $authorizedCompanies, ?int $companyId = null): array
    {
        [$scopedFilters, $companyIds] = self::scope($filters, $authorizedCompanies, $companyId);
        $debt = $companyIds === [] ? null : DebtSummaryService::stats($scopedFilters);

        return [
            'debt_total' => (float) ($debt?->total_sum ?? 0),
            'debt_paid' => (float) ($debt?->paid_sum ?? 0),
            'debt_remaining' => (float) ($debt?->remaining_sum ?? 0),
            'debt_overdue' => (float) ($debt?->overdue_sum ?? 0),
        ];
    }

    /**
     * @param  array<string, float>  $amounts
     * @return array<string, float|null>
     */
    public static function indicatorRates(string $group, array $amounts): array
    {
        [$metrics, $total] = match ($group) {
            'treasury' => [['incoming', 'outgoing'], ($amounts['incoming'] ?? 0) + ($amounts['outgoing'] ?? 0)],
            'receipts_and_custody' => [['receipt_cash', 'receipt_non_cash'], ($amounts['receipt_cash'] ?? 0) + ($amounts['receipt_non_cash'] ?? 0)],
            'settlements' => [['fee_direct', 'fee_personal'], ($amounts['fee_direct'] ?? 0) + ($amounts['fee_personal'] ?? 0)],
            'payroll' => [['payroll_paid', 'payroll_remaining'], $amounts['payroll_net'] ?? 0],
            'collection' => [['collection_paid', 'collection_remaining'], $amounts['collection_net'] ?? 0],
            'debts' => [['debt_paid', 'debt_remaining', 'debt_overdue'], $amounts['debt_total'] ?? 0],
            default => [[], 0],
        };

        return array_map(
            fn (float $amount): ?float => $total > 0 && $amount >= 0 && $amount <= $total ? $amount * 100 / $total : null,
            array_intersect_key($amounts, array_flip($metrics)),
        );
    }

    public static function statement(string $type, array $filters, array $authorizedCompanies, ?int $companyId = null, int|string|null $departmentId = null): LengthAwarePaginator
    {
        return self::statementQuery($type, $filters, $authorizedCompanies, $companyId, $departmentId)->paginate(15, ['*'], 'statementPage');
    }

    public static function preview(string $type, array $filters, array $authorizedCompanies): Collection
    {
        return self::statementQuery($type, $filters, $authorizedCompanies)->limit(5)->get();
    }

    private static function statementQuery(string $type, array $filters, array $authorizedCompanies, ?int $companyId = null, int|string|null $departmentId = null): Builder
    {
        self::authorizeStatement($type, $filters);
        if (self::isTreasuryMetric($type)) {
            return TreasurySummaryService::getPerformanceStatementQuery($filters, $type, $authorizedCompanies);
        }

        [$filters, $companyIds] = self::scope($filters, $authorizedCompanies, $companyId);
        if (self::isWorkflowMetric($type)) {
            $query = ServiceFinancialPerformanceService::query($type, $filters, $companyIds);

            return $query
                ->with(match ($type) {
                    'receipt_cash', 'receipt_non_cash', 'custody' => ['company', 'collector.user'],
                    'handover_pending', 'handover_confirmed' => ['company', 'collector.user', 'treasuryTransaction'],
                    'reimbursement_paid' => ['company', 'disbursement.paidByEmployee.user', 'treasuryTransaction'],
                    default => ['company', 'paidByEmployee.user', 'treasuryTransaction'],
                })
                ->orderByDesc($query->qualifyColumn('id'));
        }

        if (isset(self::PAYROLL_METRICS[$type])) {
            $query = PayrollSheetService::savedRecordsInPeriod($filters, $companyIds)
                ->where('operation_type', str_starts_with($type, 'payroll_') ? 'payroll' : 'collection')
                ->with(['company', 'employee.user']);
            self::applyPayrollEmployeeScope($query, $filters, $departmentId);

            return $query->orderByDesc('payroll_month')->orderByDesc('id');
        }

        if ($type === 'debt_overdue') {
            $filters['status'] = 'overdue';
        }

        return DebtSummaryService::statementQuery($filters)->whereIn('debts.company_id', $companyIds);
    }

    private static function scope(array $filters, array $authorizedCompanies, ?int $companyId = null): array
    {
        $selected = FilterBarService::values($filters['company_id'] ?? null);
        $companyIds = array_values($selected === [] ? $authorizedCompanies : array_intersect($authorizedCompanies, $selected));
        if ($companyId !== null) {
            $companyIds = array_values(array_intersect($companyIds, [$companyId]));
        }
        $scope = [
            'company_id' => $companyIds,
            'branch_id' => CompanyDepartmentPerformanceService::resolveBranchId($filters),
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
        ];

        return [$scope, $companyIds];
    }

    private static function applyPayrollEmployeeScope(Builder $query, array $filters, int|string|null $departmentId): void
    {
        $user = auth()->user();
        if (CompanyDepartmentPerformanceService::isSupervisorViewer($user)) {
            [$startDate, $endDate] = CompanyDepartmentPerformanceService::resolveDateRange($filters);
            $teamIds = CompanyDepartmentPerformanceService::supervisorTeamIds($user, $startDate, $endDate);
            $query->whereIn('payroll_records.employee_id', $teamIds);
        }

        if ($departmentId === 'unassigned' || $departmentId === 0) {
            $query->whereHas('employee', fn (Builder $q) => $q->whereNull('employees.department_id'));
        } elseif ($departmentId !== null) {
            $query->whereHas('employee', fn (Builder $q) => $q->where('employees.department_id', (int) $departmentId));
        }
    }

    private static function isWorkflowMetric(string $type): bool
    {
        return in_array($type, ServiceFinancialPerformanceService::TRANSFER_METRICS, true)
            || in_array($type, ServiceFinancialPerformanceService::SETTLEMENT_METRICS, true);
    }

    private static function workflowTotals(array $metrics, array $filters, array $companyIds): array
    {
        $totals = [];
        foreach ($metrics as $type) {
            $totals[$type] = $companyIds === [] ? 0.0 : ServiceFinancialPerformanceService::total(
                ServiceFinancialPerformanceService::query($type, $filters, $companyIds)
            );
        }

        return $totals;
    }
}
