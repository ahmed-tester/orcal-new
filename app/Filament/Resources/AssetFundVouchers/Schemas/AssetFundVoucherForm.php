<?php

namespace App\Filament\Resources\AssetFundVouchers\Schemas;

use App\Enums\AssetFundSourceType;
use App\Enums\AssetFundVoucherType;
use App\Models\AssetFund;
use App\Models\AssetFundVoucher;
use App\Services\CompanyFormService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AssetFundVoucherForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                // ─── Section 1: Basic Voucher Info ───────────────────────────
                Section::make(__('asset_funds.asset_fund_vouchers.sections.voucher_info'))
                    ->description(__('asset_funds.asset_fund_vouchers.sections.voucher_info_desc'))
                    ->icon('heroicon-o-document-text')
                    ->columns(3)
                    ->schema([
                        TextInput::make('voucher_number')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.voucher_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        Select::make('asset_fund_id')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.fund'))
                            ->options(fn () => AssetFund::where('status', 'active')->pluck('fund_name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get, ?AssetFundVoucher $record) {
                                if ($state && ! $record) {
                                    $type = $get('type') instanceof AssetFundVoucherType ? $get('type')->value : $get('type');
                                    if ($type === AssetFundVoucherType::DEPOSIT->value) {
                                        $fund = AssetFund::find($state);
                                        if ($fund) {
                                            $taxRate = (float) ($fund->default_tax_percentage ?? 15.00);
                                            $set('tax_percentage', $taxRate);
                                            self::recalculateDepositFromTaxPercentage($get, $set);
                                        }
                                    }
                                }
                            })
                            ->columnSpan(fn ($record) => $record !== null ? 1 : 2),

                        ToggleButtons::make('type')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.type'))
                            ->options(AssetFundVoucherType::class)
                            ->default(AssetFundVoucherType::DEPOSIT)
                            ->inline()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get, ?AssetFundVoucher $record) {
                                if (! $record) {
                                    $val = $state instanceof AssetFundVoucherType ? $state->value : $state;
                                    if ($val === AssetFundVoucherType::DEPOSIT->value) {
                                        $fundId = $get('asset_fund_id');
                                        if ($fundId) {
                                            $taxRate = (float) (AssetFund::find($fundId)?->default_tax_percentage ?? 15.00);
                                            $set('tax_percentage', $taxRate);
                                            self::recalculateDepositFromTaxPercentage($get, $set);
                                        }
                                    } else {
                                        $set('tax_percentage', null);
                                        $set('tax_amount', 0);
                                        self::recalculateWithdrawal($get, $set);
                                    }
                                }
                            })
                            ->required(),

                        DatePicker::make('voucher_date')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.voucher_date'))
                            ->default(now())
                            ->required(),
                    ]),

                // ─── Section 2A: Deposit Parties & Invoice ─────────────────────
                Section::make(__('asset_funds.asset_fund_vouchers.sections.deposit_parties'))
                    ->description(__('asset_funds.asset_fund_vouchers.sections.deposit_parties_desc'))
                    ->icon('heroicon-o-identification')
                    ->columns(2)
                    ->visible(fn (callable $get) => ($get('type') instanceof AssetFundVoucherType ? $get('type')->value : $get('type')) === AssetFundVoucherType::DEPOSIT->value || empty($get('type')))
                    ->schema([
                        // Row 1: contractor and invoice number
                        TextInput::make('contractor_name')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.contractor_name'))
                            ->placeholder(__('asset_funds.asset_fund_vouchers.placeholders.contractor_name')),

                        TextInput::make('tax_invoice_number')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.tax_invoice_number')),

                        // Row 2: establishment options next to the establishment field
                        ToggleButtons::make('source_type')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.source_type'))
                            ->options(AssetFundSourceType::class)
                            ->default(AssetFundSourceType::FREE)
                            ->inline()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $val = $state instanceof AssetFundSourceType ? $state->value : (string) $state;
                                if ($val === AssetFundSourceType::FREE->value) {
                                    $set('company_id', null);
                                } else {
                                    $set('party_name', null);
                                }
                            }),

                        CompanyFormService::makeCompanySelect(
                            name: 'company_id',
                            label: __('asset_funds.asset_fund_vouchers.fields.company'),
                            required: false,
                        )
                            ->visible(fn (callable $get) => in_array($get('source_type'), [AssetFundSourceType::COMPANY, AssetFundSourceType::COMPANY->value], true))
                            ->required(fn (callable $get) => in_array($get('source_type'), [AssetFundSourceType::COMPANY, AssetFundSourceType::COMPANY->value], true)),

                        TextInput::make('party_name')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.manual_company'))
                            ->placeholder(__('asset_funds.asset_fund_vouchers.placeholders.party_name'))
                            ->visible(fn (callable $get) => in_array($get('source_type'), [AssetFundSourceType::FREE, AssetFundSourceType::FREE->value, null, ''], true)),
                    ]),

                // ─── Section 2B: Deposit Financials & Deductions ─────────────────
                Section::make(__('asset_funds.asset_fund_vouchers.sections.deposit_financials'))
                    ->description(__('asset_funds.asset_fund_vouchers.sections.deposit_financials_desc'))
                    ->icon('heroicon-o-calculator')
                    ->columns(12)
                    ->visible(fn (callable $get) => ($get('type') instanceof AssetFundVoucherType ? $get('type')->value : $get('type')) === AssetFundVoucherType::DEPOSIT->value || empty($get('type')))
                    ->schema([
                        TextInput::make('gross_amount')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.gross_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (callable $get, callable $set) => self::recalculateDeposit($get, $set))
                            ->columnSpan(3),

                        TextInput::make('tax_percentage')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.tax_percentage'))
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(function (callable $get) {
                                $fundId = $get('asset_fund_id');
                                if ($fundId) {
                                    return (float) (AssetFund::find($fundId)?->default_tax_percentage ?? 15.00);
                                }
                                $activeFunds = AssetFund::where('status', 'active')->get();
                                if ($activeFunds->count() === 1) {
                                    return (float) ($activeFunds->first()->default_tax_percentage ?? 15.00);
                                }
                                return 15.00;
                            })
                            ->dehydrated(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (callable $get, callable $set) => self::recalculateDepositFromTaxPercentage($get, $set))
                            ->columnSpan(3),

                        TextInput::make('tax_amount')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.tax_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (callable $get, callable $set) => self::recalculateDepositFromTaxAmount($get, $set))
                            ->columnSpan(3),

                        TextInput::make('net_after_tax')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.net_after_tax'))
                            ->numeric()
                            ->prefix('SAR')
                            ->readOnly()
                            ->extraAttributes(['class' => 'font-bold'])
                            ->columnSpan(3),

                        TextInput::make('retention_percentage')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.retention_percentage'))
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (callable $get, callable $set) => self::recalculateDepositDeductions($get, $set))
                            ->columnSpan(3),

                        TextInput::make('retention_amount')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.retention_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->readOnly()
                            ->columnSpan(3),

                        TextInput::make('fund_share_percentage')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.fund_share_percentage'))
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (callable $get, callable $set) => self::recalculateDepositDeductions($get, $set))
                            ->columnSpan(3),

                        TextInput::make('fund_share_amount')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.fund_share_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->readOnly()
                            ->columnSpan(3),

                        TextInput::make('net_after_deductions')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.net_after_deductions'))
                            ->numeric()
                            ->prefix('SAR')
                            ->readOnly()
                            ->columnSpan(12)
                            ->extraAttributes(['class' => 'text-xl font-bold text-success-600']),
                    ]),

                // ─── Section 3: Withdrawal Voucher Details ───────────────────────
                Section::make(__('asset_funds.asset_fund_vouchers.sections.withdrawal_details'))
                    ->description(__('asset_funds.asset_fund_vouchers.sections.withdrawal_details_desc'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->columns(2)
                    ->visible(fn (callable $get) => ($get('type') instanceof AssetFundVoucherType ? $get('type')->value : $get('type')) === AssetFundVoucherType::WITHDRAWAL->value)
                    ->schema([
                        Placeholder::make('fund_gross_balance_info')
                            ->hiddenLabel()
                            ->visible(fn (callable $get) => ($get('type') instanceof AssetFundVoucherType ? $get('type')->value : $get('type')) === AssetFundVoucherType::WITHDRAWAL->value && ! empty($get('asset_fund_id')))
                            ->content(function (callable $get, ?AssetFundVoucher $record): \Illuminate\Support\HtmlString {
                                $fundId = (int) $get('asset_fund_id');
                                $available = AssetFundVoucher::getFundGrossAvailableBalance($fundId, $record?->id);
                                $isPositive = $available > 0;
                                $color = $isPositive ? '#1e40af' : '#b91c1c';
                                $border = $isPositive ? '#bfdbfe' : '#fca5a5';
                                $bg = $isPositive ? '#eff6ff' : '#fef2f2';

                                return new \Illuminate\Support\HtmlString("
                                    <div style='background: {$bg}; border: 1px solid {$border}; border-radius: .5rem; padding: .65rem 1rem; font-size: .85rem; color: {$color}; display: flex; justify-content: space-between; align-items: center;'>
                                        <span><strong>" . __('asset_funds.asset_fund_vouchers.fields.fund_gross_available') . ":</strong></span>
                                        <span style='font-size: 1.15rem; font-weight: 800; direction: ltr;'>" . number_format($available, 2) . " <small style='font-size:.75rem'>ر.س</small></span>
                                    </div>
                                ");
                            })
                            ->columnSpanFull(),

                        // Disbursement Type (General vs Linked Deposit)
                        ToggleButtons::make('disbursement_type')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.disbursement_type'))
                            ->options([
                                'general'        => __('asset_funds.asset_fund_vouchers.disbursement_types.general'),
                                'linked_deposit' => __('asset_funds.asset_fund_vouchers.disbursement_types.linked_deposit'),
                            ])
                            ->default('general')
                            ->inline()
                            ->live()
                            ->columnSpanFull()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state !== 'linked_deposit') {
                                    $set('linked_deposit_voucher_id', null);
                                }
                            }),

                        Select::make('linked_deposit_voucher_id')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.linked_deposit_voucher'))
                            ->placeholder(__('asset_funds.asset_fund_vouchers.placeholders.linked_deposit_voucher'))
                            ->visible(fn (callable $get) => $get('disbursement_type') === 'linked_deposit')
                            ->required(fn (callable $get) => ($get('type') instanceof AssetFundVoucherType ? $get('type')->value : $get('type')) === AssetFundVoucherType::WITHDRAWAL->value && $get('disbursement_type') === 'linked_deposit')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->options(function (callable $get, ?AssetFundVoucher $record): array {
                                $fundId = $get('asset_fund_id') ?? $record?->asset_fund_id;

                                $query = AssetFundVoucher::query()
                                    ->where(function ($q) {
                                        $q->where('type', AssetFundVoucherType::DEPOSIT->value)
                                          ->orWhere('type', 'deposit');
                                    })
                                    ->with(['company', 'fund'])
                                    ->orderByDesc('voucher_date')
                                    ->orderByDesc('id');

                                if ($fundId) {
                                    $query->where('asset_fund_id', $fundId);
                                }

                                $deposits = $query->get();

                                $options = [];
                                foreach ($deposits as $dep) {
                                    $party = $dep->party_name ?: ($dep->contractor_name ?: ($dep->company?->trade_name ?? $dep->company?->name ?? '—'));
                                    $date = $dep->voucher_date?->format('Y/m/d');

                                    $options[$dep->id] = trim("{$dep->voucher_number} — {$party}" . ($date ? " — {$date}" : ''));
                                }

                                return $options;
                            })
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($state && empty($get('asset_fund_id'))) {
                                    $dep = AssetFundVoucher::find($state);
                                    if ($dep) {
                                        $set('asset_fund_id', $dep->asset_fund_id);
                                    }
                                }
                            })
                            ->columnSpanFull(),

                        // Establishment / payee options
                        ToggleButtons::make('source_type')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.source_type'))
                            ->options(AssetFundSourceType::class)
                            ->default(AssetFundSourceType::FREE)
                            ->inline()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $val = $state instanceof AssetFundSourceType ? $state->value : (string) $state;
                                if ($val === AssetFundSourceType::FREE->value) {
                                    $set('company_id', null);
                                } else {
                                    $set('party_name', null);
                                }
                            }),

                        CompanyFormService::makeCompanySelect(
                            name: 'company_id',
                            label: __('asset_funds.asset_fund_vouchers.fields.company'),
                            required: false,
                        )
                            ->visible(fn (callable $get) => in_array($get('source_type'), [AssetFundSourceType::COMPANY, AssetFundSourceType::COMPANY->value], true))
                            ->required(fn (callable $get) => ($get('type') instanceof AssetFundVoucherType ? $get('type')->value : $get('type')) === AssetFundVoucherType::WITHDRAWAL->value && in_array($get('source_type'), [AssetFundSourceType::COMPANY, AssetFundSourceType::COMPANY->value], true)),

                        TextInput::make('party_name')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.party_name'))
                            ->placeholder(__('asset_funds.asset_fund_vouchers.placeholders.party_name'))
                            ->helperText(__('asset_funds.asset_fund_vouchers.helpers.party_name'))
                            ->visible(fn (callable $get) => in_array($get('source_type'), [AssetFundSourceType::FREE, AssetFundSourceType::FREE->value, null, ''], true))
                            ->required(fn (callable $get) => ($get('type') instanceof AssetFundVoucherType ? $get('type')->value : $get('type')) === AssetFundVoucherType::WITHDRAWAL->value && in_array($get('source_type'), [AssetFundSourceType::FREE, AssetFundSourceType::FREE->value, null, ''], true)),

                        TextInput::make('expense_reason')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.expense_reason'))
                            ->placeholder(__('asset_funds.asset_fund_vouchers.placeholders.expense_reason'))
                            ->required(fn (callable $get) => ($get('type') instanceof AssetFundVoucherType ? $get('type')->value : $get('type')) === AssetFundVoucherType::WITHDRAWAL->value)
                            ->columnSpanFull(),

                        TextInput::make('amount_without_tax')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.amount_without_tax'))
                            ->numeric()
                            ->prefix('SAR')
                            ->required(fn (callable $get) => ($get('type') instanceof AssetFundVoucherType ? $get('type')->value : $get('type')) === AssetFundVoucherType::WITHDRAWAL->value)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (callable $get, callable $set) => self::recalculateWithdrawal($get, $set))
                            ->rules([
                                fn (callable $get, ?AssetFundVoucher $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                    $fundId = $get('asset_fund_id') ?? $record?->asset_fund_id;
                                    $tax = (float) ($get('tax_amount') ?? 0);
                                    $total = (float) $value + $tax;

                                    // 1. تحقق صارم على مستوى إجمالي الصندوق (شامل الضرائب والاستقطاعات والنسبة الأخرى) لمنع السوالب
                                    if ($fundId && $total > 0) {
                                        $fundAvailable = AssetFundVoucher::getFundGrossAvailableBalance((int) $fundId, $record?->id);
                                        if ($total > $fundAvailable) {
                                            $fail(__('asset_funds.asset_fund_vouchers.errors.amount_exceeds_fund_gross_balance', [
                                                'amount'    => number_format($total, 2),
                                                'available' => number_format($fundAvailable, 2),
                                            ]));
                                            return;
                                        }
                                    }

                                    // 2. تحقق على مستوى سند الإيداع المرتبط (إن وُجد)
                                    if ($get('disbursement_type') === 'linked_deposit' && $depositId = $get('linked_deposit_voucher_id')) {
                                        $remaining = AssetFundVoucher::getRemainingBalanceForDeposit((int) $depositId, $record?->id);
                                        if ($total > $remaining) {
                                            $fail(__('asset_funds.asset_fund_vouchers.errors.amount_exceeds_deposit_balance', [
                                                'remaining' => number_format($remaining, 2),
                                            ]));
                                        }
                                    }
                                },
                            ])
                            ->columnSpanFull(),

                        TextInput::make('tax_percentage')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.tax_percentage'))
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->dehydrated(false)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (callable $get, callable $set) => self::recalculateWithdrawalFromTaxPercentage($get, $set))
                            ->columnSpan(1),

                        TextInput::make('tax_amount')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.withdrawal_tax_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (callable $get, callable $set) => self::recalculateWithdrawalFromTaxAmount($get, $set))
                            ->columnSpan(1),

                        TextInput::make('total_amount')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.gross_with_tax'))
                            ->numeric()
                            ->prefix('SAR')
                            ->readOnly()
                            ->extraAttributes(['class' => 'text-xl font-bold text-danger-600'])
                            ->columnSpanFull(),
                    ]),

                // ─── Section 4: Notes ─────────────────────────────────────────
                Section::make(__('asset_funds.asset_fund_vouchers.fields.notes'))
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->schema([
                        Textarea::make('notes')
                            ->hiddenLabel()
                            ->rows(3),
                    ]),
            ]);
    }

    public static function recalculateDeposit(callable $get, callable $set): void
    {
        $gross = (float) ($get('gross_amount') ?? 0);
        $pct   = (float) ($get('tax_percentage') ?? 0);
        $tax   = (float) ($get('tax_amount') ?? 0);

        if ($gross <= 0) {
            $set('tax_amount', 0);
            $set('net_after_tax', 0);
            self::recalculateDepositDeductions($get, $set);

            return;
        }

        if ($pct > 0) {
            // Gross amount includes tax: Gross = Net * (1 + pct / 100) => Net = Gross / (1 + pct / 100)
            $net = round($gross / (1 + ($pct / 100)), 2);
            $tax = round($gross - $net, 2);
            $set('tax_amount', $tax);
            $set('net_after_tax', $net);
        } elseif ($tax > 0) {
            $net = round(max(0, $gross - $tax), 2);
            $set('net_after_tax', $net);
            if ($net > 0) {
                $set('tax_percentage', round(($tax / $net) * 100, 2));
            }
        } else {
            $set('tax_amount', 0);
            $set('net_after_tax', $gross);
        }

        self::recalculateDepositDeductions($get, $set);
    }

    public static function recalculateDepositFromTaxPercentage(callable $get, callable $set): void
    {
        $gross = (float) ($get('gross_amount') ?? 0);
        $pct   = (float) ($get('tax_percentage') ?? 0);

        if ($gross <= 0) {
            $set('tax_amount', 0);
            $set('net_after_tax', 0);
            self::recalculateDepositDeductions($get, $set);

            return;
        }

        if ($pct > 0) {
            $net = round($gross / (1 + ($pct / 100)), 2);
            $tax = round($gross - $net, 2);
            $set('tax_amount', $tax);
            $set('net_after_tax', $net);
        } else {
            $set('tax_amount', 0);
            $set('net_after_tax', $gross);
        }

        self::recalculateDepositDeductions($get, $set);
    }

    public static function recalculateDepositFromTaxAmount(callable $get, callable $set): void
    {
        $gross = (float) ($get('gross_amount') ?? 0);
        $tax   = (float) ($get('tax_amount') ?? 0);

        if ($gross <= 0) {
            $set('tax_amount', 0);
            $set('net_after_tax', 0);
            $set('tax_percentage', null);
            self::recalculateDepositDeductions($get, $set);

            return;
        }

        $net = round(max(0, $gross - $tax), 2);
        $set('net_after_tax', $net);

        if ($net > 0 && $tax > 0) {
            $set('tax_percentage', round(($tax / $net) * 100, 2));
        } else {
            $set('tax_percentage', null);
        }

        self::recalculateDepositDeductions($get, $set);
    }

    public static function recalculateDepositDeductions(callable $get, callable $set): void
    {
        $net = (float) ($get('net_after_tax') ?? 0);

        $retPct = (float) ($get('retention_percentage') ?? 0);
        $retAmt = round($net * ($retPct / 100), 2);
        $set('retention_amount', $retAmt);

        $fndPct = (float) ($get('fund_share_percentage') ?? 0);
        $fndAmt = round($net * ($fndPct / 100), 2);
        $set('fund_share_amount', $fndAmt);

        $afterDeductions = round($net - $retAmt - $fndAmt, 2);
        $set('net_after_deductions', $afterDeductions);
        $set('total_amount', $afterDeductions);
    }

    public static function recalculateWithdrawal(callable $get, callable $set): void
    {
        $net = (float) ($get('amount_without_tax') ?? 0);
        $pct = (float) ($get('tax_percentage') ?? 0);
        $tax = (float) ($get('tax_amount') ?? 0);

        if ($pct > 0) {
            $tax = round($net * ($pct / 100), 2);
            $set('tax_amount', $tax);
        } elseif ($tax > 0 && $net > 0) {
            $set('tax_percentage', round(($tax / $net) * 100, 2));
        }

        $total = round($net + $tax, 2);

        $set('gross_amount', $total);
        $set('total_amount', $total);
    }

    public static function recalculateWithdrawalFromTaxPercentage(callable $get, callable $set): void
    {
        $net = (float) ($get('amount_without_tax') ?? 0);
        $pct = (float) ($get('tax_percentage') ?? 0);

        $tax = $pct > 0 ? round($net * ($pct / 100), 2) : 0.0;
        $set('tax_amount', $tax);

        $total = round($net + $tax, 2);
        $set('gross_amount', $total);
        $set('total_amount', $total);
    }

    public static function recalculateWithdrawalFromTaxAmount(callable $get, callable $set): void
    {
        $net = (float) ($get('amount_without_tax') ?? 0);
        $tax = (float) ($get('tax_amount') ?? 0);

        if ($net > 0 && $tax > 0) {
            $set('tax_percentage', round(($tax / $net) * 100, 2));
        } else {
            $set('tax_percentage', null);
        }

        $total = round($net + $tax, 2);
        $set('gross_amount', $total);
        $set('total_amount', $total);
    }

    /**
     * Derives the tax percentage of a saved voucher from the stored tax amount and base,
     * so the field is prefilled when editing and can be used for recalculation.
     */
    public static function deriveTaxPercentage(?AssetFundVoucher $record): ?float
    {
        if (! $record instanceof AssetFundVoucher) {
            return null;
        }

        $type = $record->type instanceof AssetFundVoucherType
            ? $record->type->value
            : (string) $record->type;

        $base = (float) ($type === AssetFundVoucherType::WITHDRAWAL->value
            ? $record->amount_without_tax
            : $record->net_after_tax);

        $tax = (float) ($record->tax_amount ?? 0);

        if ($base <= 0 || $tax <= 0) {
            return null;
        }

        return round($tax / $base * 100, 2);
    }
}
