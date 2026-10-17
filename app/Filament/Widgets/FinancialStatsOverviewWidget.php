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

    protected function getStats(): array
    {
        $user = auth()->user();
        $companyIds = [];

        if ($user) {
            if ($user->isSuperAdmin()) {
                $companyIds = Company::pluck('id')->toArray();
            } elseif ($user->isOwner()) {
                $companyIds = $user->getOwnedCompanyIds();
            } elseif ($user->company_id) {
                $companyIds = [$user->company_id];
            }
        }

        $currency = __('dashboard.stats.currency');

        // 1. Current Treasury Balance (Sum of latest running balances for all scoped companies)
        $totalTreasuryBalance = 0;
        $activeWithBalance = [];
        if (! empty($companyIds)) {
            foreach ($companyIds as $cId) {
                $comp = Company::find($cId);
                $latestTx = TreasuryTransaction::where('company_id', $cId)->orderByDesc('id')->first();
                $b = $latestTx ? (float) $latestTx->balance_after : 0.0;
                $totalTreasuryBalance += $b;
                if ($comp && abs($b) > 0.01) {
                    $activeWithBalance[] = $comp->trade_name . ': ' . number_format($b, 2) . ' ' . $currency;
                }
            }
        } else {
            $latestTx = TreasuryTransaction::orderByDesc('id')->first();
            $totalTreasuryBalance = $latestTx ? (float) $latestTx->balance_after : 0.0;
        }

        $treasuryDescription = match (true) {
            count($companyIds) === 1 => Company::find($companyIds[0])?->trade_name ?? __('dashboard.stats.treasury_balance_desc'),
            count($activeWithBalance) > 0 && count($activeWithBalance) <= 3 => implode(' | ', $activeWithBalance),
            count($companyIds) > 1 => __('dashboard.stats.treasury_balance_desc') . ' (' . count($companyIds) . ' منشأة)',
            default => __('dashboard.stats.treasury_balance_desc'),
        };

        // 2. Total Invoiced & Daily Sales
        $invoicesPaid = Invoice::when(! empty($companyIds), fn ($q) => $q->whereIn('company_id', $companyIds))->sum('paid_amount');
        $dailySales   = DailyJournal::when(! empty($companyIds), fn ($q) => $q->whereIn('company_id', $companyIds))->sum('sales');
        $totalRevenue = (float) $invoicesPaid + (float) $dailySales;

        // 3. Current Payroll Records (رواتب فقط للشهر الحالي)
        $payrollBaseQuery = PayrollRecord::when(! empty($companyIds), fn ($q) => $q->whereIn('company_id', $companyIds))
            ->where('operation_type', 'payroll')
            ->whereMonth('payroll_month', now()->month)
            ->whereYear('payroll_month', now()->year);

        $payrollTotalNet  = (float) (clone $payrollBaseQuery)->sum('net_amount');
        $payrollPaid      = (float) (clone $payrollBaseQuery)->sum('paid_amount');
        $payrollRemaining = max(0, $payrollTotalNet - $payrollPaid);

        // 4. Installments & Advances Portfolio
        $installmentsQuery     = Installment::when(! empty($companyIds), fn ($q) => $q->whereIn('company_id', $companyIds));
        $installmentsTotal     = (float) $installmentsQuery->sum('total_amount');
        $installmentsPaid      = (float) $installmentsQuery->sum('paid_amount');
        $installmentsRemaining = (float) $installmentsQuery->sum('remaining_amount');

        return [
            Stat::make(__('dashboard.stats.treasury_balance'), number_format($totalTreasuryBalance, 2) . ' ' . $currency)
                ->description($treasuryDescription)
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($totalTreasuryBalance >= 0 ? 'success' : 'danger')
                ->chart([
                    max(0, $totalTreasuryBalance * 0.7),
                    max(0, $totalTreasuryBalance * 0.85),
                    max(0, $totalTreasuryBalance * 0.9),
                    $totalTreasuryBalance,
                ]),

            Stat::make(__('dashboard.stats.total_revenue'), number_format($totalRevenue, 2) . ' ' . $currency)
                ->description(__('dashboard.stats.total_revenue_desc'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary')
                ->chart([
                    max(0, $totalRevenue * 0.5),
                    max(0, $totalRevenue * 0.75),
                    max(0, $totalRevenue * 0.9),
                    $totalRevenue,
                ]),

            Stat::make(__('dashboard.stats.monthly_payroll'), number_format($payrollPaid, 2) . ' ' . $currency)
                ->description(__('dashboard.stats.monthly_payroll_desc', [
                    'paid'      => number_format($payrollPaid, 2),
                    'remaining' => number_format($payrollRemaining, 2),
                ]))
                ->descriptionIcon('heroicon-m-calculator')
                ->color($payrollRemaining > 0 ? 'warning' : 'success')
                ->chart([
                    max(0, $payrollPaid * 0.6),
                    max(0, $payrollPaid * 0.8),
                    $payrollPaid,
                ]),

            Stat::make(__('dashboard.stats.installments_portfolio'), number_format($installmentsTotal, 2) . ' ' . $currency)
                ->description(__('dashboard.stats.installments_portfolio_desc', [
                    'paid'      => number_format($installmentsPaid, 2),
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
