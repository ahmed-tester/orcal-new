<?php

namespace App\Filament\Resources\ViolationTransactions\Widgets;

use App\Enums\ViolationPaymentStatus;
use App\Enums\ViolationTransactionType;
use App\Models\Company;
use App\Models\Violation;
use App\Models\ViolationTransaction;
use App\Services\CompanyFormService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ViolationTreasuryStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $companyIds = CompanyFormService::getAuthorizedCompanyIds($user);

        // 1. Total Collected (Incoming)
        $totalCollected = (float) ViolationTransaction::query()
            ->when(!empty($companyIds), fn ($q) => $q->whereIn('company_id', $companyIds))
            ->where('transaction_type', ViolationTransactionType::INCOMING->value)
            ->sum('amount');

        // 2. Total Disbursed (Outgoing)
        $totalDisbursed = (float) ViolationTransaction::query()
            ->when(!empty($companyIds), fn ($q) => $q->whereIn('company_id', $companyIds))
            ->where('transaction_type', ViolationTransactionType::OUTGOING->value)
            ->sum('amount');

        // 3. Current Fund Balance
        $currentBalance = 0.0;
        if (!empty($companyIds)) {
            foreach ($companyIds as $cId) {
                $latestTx = ViolationTransaction::where('company_id', $cId)->orderByDesc('id')->first();
                if ($latestTx) {
                    $currentBalance += (float) $latestTx->balance_after;
                }
            }
        } else {
            $latestTx = ViolationTransaction::orderByDesc('id')->first();
            $currentBalance = $latestTx ? (float) $latestTx->balance_after : 0.0;
        }

        // 4. Pending Violations
        $pendingViolationsCount = Violation::query()
            ->when(!empty($companyIds), fn ($q) => $q->whereIn('company_id', $companyIds))
            ->where('payment_status', '!=', ViolationPaymentStatus::PAID->value)
            ->count();

        $pendingViolationsAmount = (float) Violation::query()
            ->when(!empty($companyIds), fn ($q) => $q->whereIn('company_id', $companyIds))
            ->where('payment_status', '!=', ViolationPaymentStatus::PAID->value)
            ->sum('remaining_amount');

        $unit = __('violations.transactions.stats.unit');

        return [
            Stat::make(__('violations.transactions.stats.total_collected'), number_format($totalCollected, 2) . ' ' . $unit)
                ->description(__('violations.transactions.stats.total_collected_desc'))
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('success')
                ->chart([
                    max(0, $totalCollected * 0.4),
                    max(0, $totalCollected * 0.7),
                    max(0, $totalCollected * 0.9),
                    $totalCollected,
                ]),

            Stat::make(__('violations.transactions.stats.total_disbursed'), number_format($totalDisbursed, 2) . ' ' . $unit)
                ->description(__('violations.transactions.stats.total_disbursed_desc'))
                ->descriptionIcon('heroicon-m-arrow-up-tray')
                ->color('danger')
                ->chart([
                    max(0, $totalDisbursed * 0.4),
                    max(0, $totalDisbursed * 0.7),
                    max(0, $totalDisbursed * 0.9),
                    $totalDisbursed,
                ]),

            Stat::make(__('violations.transactions.stats.current_balance'), number_format($currentBalance, 2) . ' ' . $unit)
                ->description(__('violations.transactions.stats.current_balance_desc'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($currentBalance >= 0 ? 'info' : 'danger')
                ->chart([
                    max(0, $currentBalance * 0.5),
                    max(0, $currentBalance * 0.8),
                    $currentBalance,
                ]),

            Stat::make(__('violations.transactions.stats.pending_violations'), number_format($pendingViolationsAmount, 2) . ' ' . $unit)
                ->description(__('violations.transactions.stats.pending_violations_desc') . " ({$pendingViolationsCount})")
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->chart([
                    max(0, $pendingViolationsAmount * 0.6),
                    max(0, $pendingViolationsAmount * 0.8),
                    $pendingViolationsAmount,
                ]),
        ];
    }
}
