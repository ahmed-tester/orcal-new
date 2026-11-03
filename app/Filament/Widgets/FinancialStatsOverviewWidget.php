<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\DailyJournal;
use App\Models\Installment;
use App\Models\Invoice;
use App\Models\PayrollRecord;
use App\Models\TreasuryTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialStatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected ?string $pollingInterval = '10s';

    protected function getStats(): array
    {
        $user = auth()->user();
        $companyIds = [];

        if ($user) {
            $companyIds = $user->getAccessibleCompanyIds();
        }

        $currency = __('dashboard.stats.currency');

        // 1. Current Treasury Balance (Sum of latest running balances for all scoped companies)
        $totalTreasuryBalance = 0.0;
        if (! empty($companyIds)) {
            $latestTxIds = TreasuryTransaction::whereIn('company_id', $companyIds)
                ->selectRaw('MAX(id) as id')
                ->groupBy('company_id')
                ->pluck('id');

            if ($latestTxIds->isNotEmpty()) {
                $totalTreasuryBalance = (float) TreasuryTransaction::whereIn('id', $latestTxIds)->sum('balance_after');
            }
        } else {
            $latestTx = TreasuryTransaction::orderByDesc('id')->first();
            $totalTreasuryBalance = $latestTx ? (float) $latestTx->balance_after : 0.0;
        }

        $treasuryDescription = __('dashboard.stats.treasury_balance_desc');
        if (count($companyIds) === 1) {
            $comp = Company::find($companyIds[0]);
            $treasuryDescription = $comp?->trade_name ?: $comp?->name ?: $treasuryDescription;
        } elseif (count($companyIds) > 1) {
            $treasuryDescription = __('dashboard.stats.treasury_balance_scoped', ['count' => count($companyIds)]);
        }

        // 2. Total Invoiced & Daily Sales
        $invoicesPaid = (float) Invoice::when(! empty($companyIds), fn ($q) => $q->whereIn('company_id', $companyIds))->sum('paid_amount');
        $dailySales = (float) DailyJournal::when(! empty($companyIds), fn ($q) => $q->whereIn('company_id', $companyIds))->sum('sales');
        $totalRevenue = $invoicesPaid + $dailySales;

        // 3. Current Payroll Records (رواتب فقط للشهر الحالي)
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $payrollAggregates = PayrollRecord::when(! empty($companyIds), fn ($q) => $q->whereIn('company_id', $companyIds))
            ->where('operation_type', 'payroll')
            ->whereBetween('payroll_month', [$startOfMonth, $endOfMonth])
            ->selectRaw('COALESCE(SUM(net_amount), 0) as total_net, COALESCE(SUM(paid_amount), 0) as total_paid')
            ->first();

        $payrollTotalNet = (float) ($payrollAggregates?->total_net ?? 0);
        $payrollPaid = (float) ($payrollAggregates?->total_paid ?? 0);
        $payrollRemaining = max(0, $payrollTotalNet - $payrollPaid);

        // 4. Installments & Advances Portfolio
        $installmentAggregates = Installment::when(! empty($companyIds), fn ($q) => $q->whereIn('company_id', $companyIds))
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount, COALESCE(SUM(paid_amount), 0) as total_paid, COALESCE(SUM(remaining_amount), 0) as total_remaining')
            ->first();

        $installmentsTotal = (float) ($installmentAggregates?->total_amount ?? 0);
        $installmentsPaid = (float) ($installmentAggregates?->total_paid ?? 0);
        $installmentsRemaining = (float) ($installmentAggregates?->total_remaining ?? 0);

        return [
            Stat::make(__('dashboard.stats.treasury_balance'), number_format($totalTreasuryBalance, 2).' '.$currency)
                ->description($treasuryDescription)
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($totalTreasuryBalance >= 0 ? 'success' : 'danger')
                ->chart([
                    max(0, $totalTreasuryBalance * 0.7),
                    max(0, $totalTreasuryBalance * 0.85),
                    max(0, $totalTreasuryBalance * 0.9),
                    $totalTreasuryBalance,
                ]),

            Stat::make(__('dashboard.stats.total_revenue'), number_format($totalRevenue, 2).' '.$currency)
                ->description(__('dashboard.stats.total_revenue_desc'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary')
                ->chart([
                    max(0, $totalRevenue * 0.5),
                    max(0, $totalRevenue * 0.75),
                    max(0, $totalRevenue * 0.9),
                    $totalRevenue,
                ]),

            Stat::make(__('dashboard.stats.monthly_payroll'), number_format($payrollPaid, 2).' '.$currency)
                ->description(__('dashboard.stats.monthly_payroll_desc', [
                    'paid' => number_format($payrollPaid, 2),
                    'remaining' => number_format($payrollRemaining, 2),
                ]))
                ->descriptionIcon('heroicon-m-calculator')
                ->color($payrollRemaining > 0 ? 'warning' : 'success')
                ->chart([
                    max(0, $payrollPaid * 0.6),
                    max(0, $payrollPaid * 0.8),
                    $payrollPaid,
                ]),

            Stat::make(__('dashboard.stats.installments_portfolio'), number_format($installmentsTotal, 2).' '.$currency)
                ->description(__('dashboard.stats.installments_portfolio_desc', [
                    'paid' => number_format($installmentsPaid, 2),
                    'remaining' => number_format($installmentsRemaining, 2),
                ]))
                ->descriptionIcon('heroicon-m-clock')
                ->color('info')
                ->chart([
                    max(0, $installmentsPaid),
                    max(0, $installmentsRemaining),
                    max(0, $installmentsTotal),
                ]),
        ];
    }
}
