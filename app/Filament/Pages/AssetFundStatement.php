<?php

namespace App\Filament\Pages;

use App\Models\AssetFund;
use App\Models\AssetFundVoucher;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;

class AssetFundStatement extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?int $navigationSort = 22;

    protected string $view = 'filament.pages.asset-fund-statement';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdminOrOwner() || $user->can('view_asset_fund_statement') || $user->can('view_any_asset_fund')) {
            return true;
        }

        $employeeId = $user->employee?->id;
        if ($employeeId && AssetFund::where('manager_employee_id', $employeeId)->orWhere('accountant_employee_id', $employeeId)->exists()) {
            return true;
        }

        return false;
    }

    // ─── Filter State ─────────────────────────────────────────────────────
    public ?int    $fund_id     = null;
    public ?string $date_from   = null;
    public ?string $date_to     = null;
    public string  $filter_type = 'all';

    public function getTitle(): string
    {
        return __('asset_funds.statement.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.asset_fund_statement');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.public_finance_and_services');
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    // ─── Data Getters ──────────────────────────────────────────────────────

    public function getFundsProperty(): Collection
    {
        $user = auth()->user();
        $query = AssetFund::orderBy('fund_name');

        if ($user && ! $user->isSuperAdminOrOwner() && ! $user->can('view_any_asset_fund') && ! $user->can('view_asset_fund_statement')) {
            $employeeId = $user->employee?->id;
            if ($employeeId) {
                $query->where(function ($q) use ($employeeId) {
                    $q->where('manager_employee_id', $employeeId)
                      ->orWhere('accountant_employee_id', $employeeId);
                });
            }
        }

        return $query->get(['id', 'fund_name', 'fund_number']);
    }

    public function getVouchersProperty(): Collection
    {
        if (! $this->fund_id) {
            return collect();
        }

        $query = AssetFundVoucher::with(['company', 'creator'])
            ->where('asset_fund_id', $this->fund_id);

        if ($this->filter_type !== 'all') {
            $query->where('type', $this->filter_type);
        }

        if ($this->date_from) {
            $query->whereDate('voucher_date', '>=', $this->date_from);
        }

        if ($this->date_to) {
            $query->whereDate('voucher_date', '<=', $this->date_to);
        }

        return $query->orderBy('voucher_date')->get();
    }

    public function getDepositVouchersProperty(): Collection
    {
        return $this->vouchers->filter(fn ($v) => ($v->type?->value ?? $v->type) === 'deposit')->values();
    }

    public function getWithdrawalVouchersProperty(): Collection
    {
        return $this->vouchers->filter(fn ($v) => ($v->type?->value ?? $v->type) === 'withdrawal')->values();
    }

    public function getDepositsSummaryProperty(): array
    {
        $deposits = $this->deposit_vouchers;

        return [
            'gross_amount'         => (float) $deposits->sum('gross_amount'),
            'tax_amount'           => (float) $deposits->sum('tax_amount'),
            'net_after_tax'        => (float) $deposits->sum('net_after_tax'),
            'retention_amount'     => (float) $deposits->sum('retention_amount'),
            'fund_share_amount'    => (float) $deposits->sum('fund_share_amount'),
            'net_after_deductions' => (float) $deposits->sum('total_amount'),
        ];
    }

    public function getWithdrawalsSummaryProperty(): array
    {
        $withdrawals = $this->withdrawal_vouchers;

        return [
            'amount_without_tax' => (float) $withdrawals->sum('amount_without_tax'),
            'tax_amount'         => (float) $withdrawals->sum('tax_amount'),
            'total_amount'       => (float) $withdrawals->sum('total_amount'),
        ];
    }

    public function getSummaryProperty(): array
    {
        $depositsTotal    = (float) $this->deposit_vouchers->sum('total_amount');
        $withdrawalsTotal = (float) $this->withdrawal_vouchers->sum('total_amount');

        return [
            'total_deposits'    => $depositsTotal,
            'total_withdrawals' => $withdrawalsTotal,
            'net_balance'       => $depositsTotal - $withdrawalsTotal,
        ];
    }

    public function getSelectedFundProperty(): ?AssetFund
    {
        return $this->fund_id ? AssetFund::find($this->fund_id) : null;
    }

    // ─── Actions ──────────────────────────────────────────────────────────

    public function applyFilter(): void
    {
        // Livewire reactive
    }

    public function exportPdf(): void
    {
        if (! $this->fund_id) {
            return;
        }

        $params = http_build_query([
            'fund_id'     => $this->fund_id,
            'date_from'   => $this->date_from,
            'date_to'     => $this->date_to,
            'filter_type' => $this->filter_type,
        ]);

        $this->js("window.open('/asset-fund-statement/pdf?" . $params . "', '_blank')");
    }
}
