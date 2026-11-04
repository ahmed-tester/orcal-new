<?php

namespace App\Filament\Pages;

use App\Models\AssetFund;
use App\Models\AssetFundVoucher;
use App\Services\AssetFundStatementService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Livewire\Attributes\Session;

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

    /**
     * Statement purpose preset: all, tax, deductions, totals, custom.
     */
    #[Session]
    public string $preset = 'all';

    /**
     * Selected optional deposit table columns (kept per session).
     *
     * @var array<string>
     */
    #[Session]
    public array $deposit_columns = [
        'gross_amount', 'tax_amount', 'net_after_tax', 'retention_pct',
        'fund_share_pct', 'net_after_deductions', 'notes',
    ];

    /**
     * Selected optional withdrawal table columns (kept per session).
     *
     * @var array<string>
     */
    #[Session]
    public array $withdrawal_columns = [
        'amount_without_tax', 'tax_amount', 'gross_amount', 'notes',
    ];

    /**
     * Selected summary cards (kept per session).
     *
     * @var array<string>
     */
    #[Session]
    public array $summary_cards = [
        'total_deposits', 'total_withdrawals', 'total_tax', 'total_retention', 'net_balance',
    ];

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
        return AssetFundStatementService::depositsSummary($this->deposit_vouchers);
    }

    public function getWithdrawalsSummaryProperty(): array
    {
        return AssetFundStatementService::withdrawalsSummary($this->withdrawal_vouchers);
    }

    public function getSummaryProperty(): array
    {
        return AssetFundStatementService::summary($this->deposit_vouchers, $this->withdrawal_vouchers);
    }

    public function getSelectedFundProperty(): ?AssetFund
    {
        return $this->fund_id ? AssetFund::find($this->fund_id) : null;
    }

    /**
     * @return array<string, string>
     */
    public function getDepositColumnOptionsProperty(): array
    {
        return AssetFundStatementService::depositColumnOptions();
    }

    /**
     * @return array<string, string>
     */
    public function getWithdrawalColumnOptionsProperty(): array
    {
        return AssetFundStatementService::withdrawalColumnOptions();
    }

    /**
     * @return array<string, string>
     */
    public function getPresetOptionsProperty(): array
    {
        return [
            'all'        => __('asset_funds.statement.columns_panel.presets.all'),
            'tax'        => __('asset_funds.statement.columns_panel.presets.tax'),
            'deductions' => __('asset_funds.statement.columns_panel.presets.deductions'),
            'custom'     => __('asset_funds.statement.columns_panel.presets.custom'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getSummaryCardOptionsProperty(): array
    {
        return AssetFundStatementService::summaryCards();
    }

    /**
     * @return array<string>
     */
    public function getSelectedDepositColumnsProperty(): array
    {
        return AssetFundStatementService::resolveActiveDepositColumns(
            $this->deposit_columns,
        );
    }

    /**
     * @return array<string>
     */
    public function getSelectedWithdrawalColumnsProperty(): array
    {
        return AssetFundStatementService::resolveActiveWithdrawalColumns(
            $this->withdrawal_columns,
        );
    }

    /**
     * @return array<string>
     */
    public function getSelectedCardsProperty(): array
    {
        return AssetFundStatementService::sanitizeColumns(
            $this->summary_cards,
            array_keys(AssetFundStatementService::summaryCards()),
        );
    }

    // ─── Actions & Lifecycle Hooks ───────────────────────────────────────

    public function updatedPreset(string $value): void
    {
        $this->applyPreset($value);
    }

    public function updatedDepositColumns(): void
    {
        $this->preset = 'custom';
    }

    public function updatedWithdrawalColumns(): void
    {
        $this->preset = 'custom';
    }

    public function updatedSummaryCards(): void
    {
        $this->preset = 'custom';
    }

    public function applyFilter(): void
    {
        // Livewire reactive
    }

    /**
     * Apply a ready-made column/card preset.
     */
    public function applyPreset(string $preset): void
    {
        $this->preset = $preset;

        $deposit = array_keys(AssetFundStatementService::customizableDepositColumns());
        $withdrawal = array_keys(AssetFundStatementService::customizableWithdrawalColumns());
        $cards = array_keys(AssetFundStatementService::summaryCards());

        switch ($preset) {
            case 'tax':
                $this->deposit_columns = ['gross_amount', 'tax_amount', 'net_after_tax'];
                $this->withdrawal_columns = ['amount_without_tax', 'tax_amount', 'gross_amount'];
                $this->summary_cards = ['total_tax'];
                break;

            case 'deductions':
                $this->deposit_columns = ['gross_amount', 'net_after_tax', 'retention_pct', 'fund_share_pct', 'net_after_deductions'];
                $this->withdrawal_columns = ['gross_amount'];
                $this->summary_cards = ['total_retention'];
                break;

            case 'custom':
                // Retains manual selections
                break;

            case 'all':
            default:
                $this->preset = 'all';
                $this->deposit_columns = $deposit;
                $this->withdrawal_columns = $withdrawal;
                $this->summary_cards = $cards;
                break;
        }
    }

    /**
     * Select every column/card of one group (deposit, withdrawal, cards).
     */
    public function selectAllColumns(string $group): void
    {
        $this->preset = 'custom';

        match ($group) {
            'deposit' => $this->deposit_columns = array_keys(AssetFundStatementService::customizableDepositColumns()),
            'withdrawal' => $this->withdrawal_columns = array_keys(AssetFundStatementService::customizableWithdrawalColumns()),
            'cards' => $this->summary_cards = array_keys(AssetFundStatementService::summaryCards()),
            default => null,
        };
    }

    /**
     * Clear every column/card of one group (deposit, withdrawal, cards).
     */
    public function clearColumns(string $group): void
    {
        $this->preset = 'custom';

        match ($group) {
            'deposit' => $this->deposit_columns = [],
            'withdrawal' => $this->withdrawal_columns = [],
            'cards' => $this->summary_cards = [],
            default => null,
        };
    }

    public function exportPdf(): void
    {
        if (! $this->fund_id) {
            return;
        }

        $params = http_build_query([
            'fund_id'         => $this->fund_id,
            'date_from'       => $this->date_from,
            'date_to'         => $this->date_to,
            'filter_type'     => $this->filter_type,
            'preset'          => $this->preset,
            'deposit_cols'    => implode(',', $this->selected_deposit_columns),
            'withdrawal_cols' => implode(',', $this->selected_withdrawal_columns),
            'cards'           => implode(',', $this->selected_cards),
        ]);

        $this->js("window.open('/asset-fund-statement/pdf?" . $params . "', '_blank')");
    }
}
