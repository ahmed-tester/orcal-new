<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\DailyJournal;
use App\Models\Installment;
use App\Models\InstallmentPayment;
use App\Models\Invoice;
use App\Models\PayrollRecord;
use App\Models\TreasuryTransaction;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialStatsOverviewWidget extends BaseWidget
{
    use InteractsWithPageFilters;

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

        $startDate = $this->pageFilters['startDate'] ?? null;
        $endDate = $this->pageFilters['endDate'] ?? null;

        $currency = __('dashboard.stats.currency');

        // 1. Current Treasury Balance (Sum of latest running balances for all scoped companies)
        $totalTreasuryBalance = 0.0;
        if (! empty($companyIds)) {
            $latestTxIds = TreasuryTransaction::whereIn('company_id', $companyIds)
                ->when($endDate, fn ($q) => $q->whereDate('transaction_date', '<=', $endDate))
                ->selectRaw('MAX(id) as id')
                ->groupBy('company_id')
                ->pluck('id');

            if ($latestTxIds->isNotEmpty()) {
                $totalTreasuryBalance = (float) TreasuryTransaction::whereIn('id', $latestTxIds)->sum('balance_after');
            }
        } else {
            $latestTx = TreasuryTransaction::query()
                ->when($endDate, fn ($q) => $q->whereDate('transaction_date', '<=', $endDate))
                ->orderByDesc('id')
                ->first();
            $totalTreasuryBalance = $latestTx ? (float) $latestTx->balance_after : 0.0;
        }

        if ($startDate || $endDate) {
            $periodTxQuery = TreasuryTransaction::when(! empty($companyIds), fn ($q) => $q->whereIn('company_id', $companyIds))
                ->when($startDate, fn ($q) => $q->whereDate('transaction_date', '>=', $startDate))
                ->when($endDate, fn ($q) => $q->whereDate('transaction_date', '<=', $endDate));

            $netFlow = (float) (clone $periodTxQuery)->selectRaw("COALESCE(SUM(CASE WHEN transaction_type = 'incoming' THEN amount ELSE -amount END), 0) as net")->value('net');
            $flowSign = $netFlow > 0 ? '+' : '';

            $treasuryTitle = __('dashboard.stats.period_treasury_flow');
            $treasuryValue = $flowSign.number_format($netFlow, 2).' '.$currency;
            $treasuryDescription = __('dashboard.stats.cumulative_balance_desc', [
                'balance' => number_format($totalTreasuryBalance, 2),
                'currency' => $currency,
            ]);
            $treasuryColor = $netFlow >= 0 ? 'success' : 'danger';
        } else {
            $treasuryTitle = __('dashboard.stats.treasury_balance');
            $treasuryValue = number_format($totalTreasuryBalance, 2).' '.$currency;
            $treasuryColor = $totalTreasuryBalance >= 0 ? 'success' : 'danger';

            $treasuryDescription = __('dashboard.stats.treasury_balance_desc');
            if (count($companyIds) === 1) {
                $comp = Company::find($companyIds[0]);
                $treasuryDescription = $comp?->trade_name ?: $comp?->name ?: $treasuryDescription;
            } elseif (count($companyIds) > 1) {
                $treasuryDescription = __('dashboard.stats.treasury_balance_scoped', ['count' => count($companyIds)]);
            }
        }

        // 2. Total Invoiced & Daily Sales
        $invoicesPaid = (float) Invoice::when(! empty($companyIds), fn ($q) => $q->whereIn('company_id', $companyIds))
            ->when($startDate, fn ($q) => $q->whereDate('issue_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('issue_date', '<=', $endDate))
            ->sum('paid_amount');

        $dailySales = (float) DailyJournal::when(! empty($companyIds), fn ($q) => $q->whereIn('company_id', $companyIds))
            ->when($startDate, fn ($q) => $q->whereDate('entry_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('entry_date', '<=', $endDate))
            ->sum('sales');

        $totalRevenue = $invoicesPaid + $dailySales;
        if ($startDate || $endDate) {
            $revenueTitle = __('dashboard.stats.period_revenue');
            $revenueDescription = __('dashboard.stats.period_revenue_desc', [
                'inv' => number_format($invoicesPaid, 2),
                'sales' => number_format($dailySales, 2),
            ]);
        } else {
            $revenueTitle = __('dashboard.stats.total_revenue');
            $revenueDescription = __('dashboard.stats.total_revenue_desc');
        }

        // 3. Current Payroll Records
        $payrollQuery = PayrollRecord::when(! empty($companyIds), fn ($q) => $q->whereIn('company_id', $companyIds))
            ->where('operation_type', 'payroll');

        if ($startDate && $endDate) {
            $payrollQuery->whereBetween('payroll_month', [$startDate, $endDate]);
        } elseif ($startDate) {
            $payrollQuery->whereDate('payroll_month', '>=', $startDate);
        } elseif ($endDate) {
            $payrollQuery->whereDate('payroll_month', '<=', $endDate);
        } else {
            $startOfMonth = now()->startOfMonth()->toDateString();
            $endOfMonth = now()->endOfMonth()->toDateString();
            $payrollQuery->whereBetween('payroll_month', [$startOfMonth, $endOfMonth]);
        }

        $payrollAggregates = $payrollQuery
            ->selectRaw('COALESCE(SUM(net_amount), 0) as total_net, COALESCE(SUM(paid_amount), 0) as total_paid')
            ->first();

        $payrollTotalNet = (float) ($payrollAggregates?->total_net ?? 0);
        $payrollPaid = (float) ($payrollAggregates?->total_paid ?? 0);
        $payrollRemaining = max(0, $payrollTotalNet - $payrollPaid);
        $payrollLabel = ($startDate || $endDate)
            ? __('dashboard.stats.payroll_period')
            : __('dashboard.stats.monthly_payroll');

        // 4. Installments & Advances Portfolio / Collections
        if ($startDate || $endDate) {
            $collectedInstallments = (float) InstallmentPayment::when(! empty($companyIds), fn ($q) => $q->whereIn('company_id', $companyIds))
                ->when($startDate, fn ($q) => $q->whereDate('payment_date', '>=', $startDate))
                ->when($endDate, fn ($q) => $q->whereDate('payment_date', '<=', $endDate))
                ->sum('amount');

            $newInstallments = (float) Installment::when(! empty($companyIds), fn ($q) => $q->whereIn('company_id', $companyIds))
                ->when($startDate, fn ($q) => $q->whereDate('installment_date', '>=', $startDate))
                ->when($endDate, fn ($q) => $q->whereDate('installment_date', '<=', $endDate))
                ->sum('total_amount');

            $installmentTitle = __('dashboard.stats.period_installments_collected');
            $installmentValue = number_format($collectedInstallments, 2).' '.$currency;
            $installmentDescription = __('dashboard.stats.period_installments_desc', [
                'new' => number_format($newInstallments, 2),
                'currency' => $currency,
            ]);
        } else {
            $installmentAggregates = Installment::when(! empty($companyIds), fn ($q) => $q->whereIn('company_id', $companyIds))
                ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount, COALESCE(SUM(paid_amount), 0) as total_paid, COALESCE(SUM(remaining_amount), 0) as total_remaining')
                ->first();

            $installmentsTotal = (float) ($installmentAggregates?->total_amount ?? 0);
            $installmentsPaid = (float) ($installmentAggregates?->total_paid ?? 0);
            $installmentsRemaining = (float) ($installmentAggregates?->total_remaining ?? 0);

            $installmentTitle = __('dashboard.stats.installments_portfolio');
            $installmentValue = number_format($installmentsTotal, 2).' '.$currency;
            $installmentDescription = __('dashboard.stats.installments_portfolio_desc', [
                'paid' => number_format($installmentsPaid, 2),
                'remaining' => number_format($installmentsRemaining, 2),
            ]);
        }

        return [
            Stat::make($treasuryTitle, $treasuryValue)
                ->description($treasuryDescription)
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($treasuryColor)
                ->chart([
                    max(0, $totalTreasuryBalance * 0.7),
                    max(0, $totalTreasuryBalance * 0.85),
                    max(0, $totalTreasuryBalance * 0.9),
                    $totalTreasuryBalance,
                ]),

            Stat::make($revenueTitle, number_format($totalRevenue, 2).' '.$currency)
                ->description($revenueDescription)
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary')
                ->chart([
                    max(0, $totalRevenue * 0.5),
                    max(0, $totalRevenue * 0.75),
                    max(0, $totalRevenue * 0.9),
                    $totalRevenue,
                ]),

            Stat::make($payrollLabel, number_format($payrollPaid, 2).' '.$currency)
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

            Stat::make($installmentTitle, $installmentValue)
                ->description($installmentDescription)
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
        ];
    }
}
