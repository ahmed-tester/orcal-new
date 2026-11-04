<?php

namespace App\Filament\Resources\AssetFundVouchers\Schemas;

use App\Enums\AssetFundSourceType;
use App\Enums\AssetFundVoucherType;
use App\Models\AssetFund;
use App\Services\CompanyFormService;
use Filament\Forms\Components\DatePicker;
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
                            ->columnSpan(fn ($record) => $record !== null ? 1 : 2),

                        ToggleButtons::make('type')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.type'))
                            ->options(AssetFundVoucherType::class)
                            ->default(AssetFundVoucherType::DEPOSIT)
                            ->inline()
                            ->live()
                            ->required(),

                        DatePicker::make('voucher_date')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.voucher_date'))
                            ->default(now())
                            ->required(),
                    ]),

                // ─── Section 2A: Deposit Parties & Invoice (المقاول والمنشأة والفاتورة) ──
                Section::make(__('asset_funds.asset_fund_vouchers.sections.deposit_parties'))
                    ->description(__('asset_funds.asset_fund_vouchers.sections.deposit_parties_desc'))
                    ->icon('heroicon-o-identification')
                    ->columns(2)
                    ->visible(fn (callable $get) => ($get('type') instanceof AssetFundVoucherType ? $get('type')->value : $get('type')) === AssetFundVoucherType::DEPOSIT->value || empty($get('type')))
                    ->schema([
                        // الصف الأول: المقاول ورقم الفاتورة
                        TextInput::make('contractor_name')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.contractor_name'))
                            ->placeholder(__('asset_funds.asset_fund_vouchers.placeholders.contractor_name')),

                        TextInput::make('tax_invoice_number')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.tax_invoice_number')),

                        // الصف الثاني: خيارات المنشأة وبجانبها حقل المنشأة مباشرة
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

                // ─── Section 2B: Deposit Financials & Deductions (المبالغ والضرائب والخصومات) ──
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
                            ->columnSpan(4),

                        TextInput::make('tax_amount')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.tax_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (callable $get, callable $set) => self::recalculateDeposit($get, $set))
                            ->columnSpan(4),

                        TextInput::make('net_after_tax')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.net_after_tax'))
                            ->numeric()
                            ->prefix('SAR')
                            ->readOnly()
                            ->extraAttributes(['class' => 'font-bold'])
                            ->columnSpan(4),

                        TextInput::make('retention_percentage')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.retention_percentage'))
                            ->numeric()
                            ->suffix('%')
                            ->minValue(0)
                            ->maxValue(100)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (callable $get, callable $set) => self::recalculateDeposit($get, $set))
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
                            ->afterStateUpdated(fn (callable $get, callable $set) => self::recalculateDeposit($get, $set))
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

                // ─── Section 3: Withdrawal Voucher Details (سند سحب / صرف) ──
                Section::make(__('asset_funds.asset_fund_vouchers.sections.withdrawal_details'))
                    ->description(__('asset_funds.asset_fund_vouchers.sections.withdrawal_details_desc'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->columns(2)
                    ->visible(fn (callable $get) => ($get('type') instanceof AssetFundVoucherType ? $get('type')->value : $get('type')) === AssetFundVoucherType::WITHDRAWAL->value)
                    ->schema([
                        // خيارات المنشأة / الجهة المصروف لها
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
                            ->columnSpan(1),

                        TextInput::make('tax_amount')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.tax_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (callable $get, callable $set) => self::recalculateWithdrawal($get, $set))
                            ->columnSpan(1),

                        TextInput::make('total_amount')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.gross_amount'))
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
        $tax   = (float) ($get('tax_amount') ?? 0);
        $net   = round($gross - $tax, 2);
        $set('net_after_tax', $net);

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
        $net   = (float) ($get('amount_without_tax') ?? 0);
        $tax   = (float) ($get('tax_amount') ?? 0);
        $total = round($net + $tax, 2);

        $set('gross_amount', $total);
        $set('total_amount', $total);
    }
}
