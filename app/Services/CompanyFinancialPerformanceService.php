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

    public static function overview(array $filters, array $authorizedCompanies): array
    {
        $treasury = self::canViewTreasury() ? TreasurySummaryService::getPerformanceStats($filters, $authorizedCompanies) : null;
        [$filters, $companyIds] = self::scope($filters, $authorizedCompanies);
        $groups = [];

        if (self::canViewWorkflow()) {
            $groups['receipts_and_custody'] = self::workflowTotals(ServiceFinancialPerformanceService::TRANSFER_METRICS, $filters, $companyIds);
            $groups['settlements'] = self::workflowTotals(ServiceFinancialPerformanceService::SETTLEMENT_METRICS, $filters, $companyIds);
        }

        if (Gate::allows('viewAny', PayrollRecord::class)) {
            $rows = PayrollSheetService::selectSavedAmounts(PayrollSheetService::savedRecordsInPeriod($filters, $companyIds))
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

        if (Gate::allows('viewAny', Debt::class)) {
            $debt = $companyIds === [] ? null : DebtSummaryService::stats($filters);
            $groups['debts'] = [
                'debt_total' => (float) ($debt?->total_sum ?? 0),
                'debt_paid' => (float) ($debt?->paid_sum ?? 0),
                'debt_remaining' => (float) ($debt?->remaining_sum ?? 0),
                'debt_overdue' => (float) ($debt?->overdue_sum ?? 0),
            ];
        }

        return ['treasury' => $treasury, 'groups' => $groups];
    }

    public static function statement(string $type, array $filters, array $authorizedCompanies): LengthAwarePaginator
    {
        return self::statementQuery($type, $filters, $authorizedCompanies)->paginate(15, ['*'], 'kashfPage');
    }

    public static function preview(string $type, array $filters, array $authorizedCompanies): Collection
    {
        return self::statementQuery($type, $filters, $authorizedCompanies)->limit(5)->get();
    }

    private static function statementQuery(string $type, array $filters, array $authorizedCompanies): Builder
    {
        self::authorizeStatement($type, $filters);
        if (self::isTreasuryMetric($type)) {
            return TreasurySummaryService::getPerformanceKashfQuery($filters, $type, $authorizedCompanies);
        }

        [$filters, $companyIds] = self::scope($filters, $authorizedCompanies);
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
            return PayrollSheetService::savedRecordsInPeriod($filters, $companyIds)
                ->where('operation_type', str_starts_with($type, 'payroll_') ? 'payroll' : 'collection')
                ->with(['company', 'employee.user'])
                ->orderByDesc('payroll_month')->orderByDesc('id');
        }

        if ($type === 'debt_overdue') {
            $filters['status'] = 'overdue';
        }

        return DebtSummaryService::statementQuery($filters)->whereIn('debts.company_id', $companyIds);
    }

    private static function scope(array $filters, array $authorizedCompanies): array
    {
        $selected = FilterBarService::values($filters['company_id'] ?? null);
        $companyIds = array_values($selected === [] ? $authorizedCompanies : array_intersect($authorizedCompanies, $selected));
        $scope = [
            'company_id' => $companyIds,
            'branch_id' => CompanyDepartmentPerformanceService::resolveBranchId($filters),
            'date_from' => $filters['date_from'] ?? null,
            'date_to' => $filters['date_to'] ?? null,
        ];

        return [$scope, $companyIds];
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
