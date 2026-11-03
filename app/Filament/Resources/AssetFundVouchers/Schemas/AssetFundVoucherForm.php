<?php

namespace App\Filament\Resources\AssetFundVouchers\Schemas;

use App\Enums\AssetFundSourceType;
use App\Enums\AssetFundVoucherType;
use App\Models\AssetFund;
use App\Services\CompanyFormService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
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
                            ->required(),

                        DatePicker::make('voucher_date')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.voucher_date'))
                            ->default(now())
                            ->required(),
                    ]),

                // ─── Section 2: Amount Source / Destination ──────────────────
                Section::make(__('asset_funds.asset_fund_vouchers.sections.source_info'))
                    ->description(__('asset_funds.asset_fund_vouchers.sections.source_info_desc'))
                    ->icon('heroicon-o-arrows-right-left')
                    ->columns(2)
                    ->schema([
                        ToggleButtons::make('source_type')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.source_type'))
                            ->options(AssetFundSourceType::class)
                            ->default(AssetFundSourceType::FREE)
                            ->inline()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $val = $state instanceof AssetFundSourceType ? $state->value : (string) $state;
                                if ($val === AssetFundSourceType::FREE->value) {
                                    $set('company_id', null);
                                } else {
                                    $set('party_name', null);
                                }
                            })
                            ->columnSpanFull(),

                        CompanyFormService::makeCompanySelect(
                            name: 'company_id',
                            label: __('asset_funds.asset_fund_vouchers.fields.company'),
                            required: false,
                        )
                            ->visible(fn (callable $get) => in_array($get('source_type'), [AssetFundSourceType::COMPANY, AssetFundSourceType::COMPANY->value], true))
                            ->required(fn (callable $get) => in_array($get('source_type'), [AssetFundSourceType::COMPANY, AssetFundSourceType::COMPANY->value], true)),

                        TextInput::make('party_name')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.party_name'))
                            ->helperText(__('asset_funds.asset_fund_vouchers.helpers.party_name'))
                            ->placeholder(__('asset_funds.asset_fund_vouchers.placeholders.party_name'))
                            ->visible(fn (callable $get) => in_array($get('source_type'), [AssetFundSourceType::FREE, AssetFundSourceType::FREE->value, null, ''], true))
                            ->required(fn (callable $get) => in_array($get('source_type'), [AssetFundSourceType::FREE, AssetFundSourceType::FREE->value, null, ''], true)),

                        Textarea::make('notes')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.notes'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                // ─── Section 3: Amount Details (Repeater) ────────────────────
                Section::make(__('asset_funds.asset_fund_vouchers.sections.items'))
                    ->description(__('asset_funds.asset_fund_vouchers.sections.items_desc'))
                    ->icon('heroicon-o-table-cells')
                    ->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->relationship('items')
                            ->orderColumn('sort_order')
                            ->columns(12)
                            ->schema([
                                TextInput::make('description')
                                    ->label(__('asset_funds.asset_fund_voucher_items.fields.description'))
                                    ->columnSpan(3),

                                TextInput::make('percentage')
                                    ->label(__('asset_funds.asset_fund_voucher_items.fields.percentage'))
                                    ->numeric()
                                    ->suffix('%')
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->columnSpan(1),

                                TextInput::make('sub_total')
                                    ->label(__('asset_funds.asset_fund_voucher_items.fields.sub_total'))
                                    ->numeric()
                                    ->prefix('SAR')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (callable $get, callable $set) => self::recalcRow($get, $set))
                                    ->columnSpan(2),

                                TextInput::make('extra_1')
                                    ->label(__('asset_funds.asset_fund_voucher_items.fields.extra_1'))
                                    ->numeric()
                                    ->prefix('SAR')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (callable $get, callable $set) => self::recalcRow($get, $set))
                                    ->columnSpan(2),

                                TextInput::make('extra_2')
                                    ->label(__('asset_funds.asset_fund_voucher_items.fields.extra_2'))
                                    ->numeric()
                                    ->prefix('SAR')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (callable $get, callable $set) => self::recalcRow($get, $set))
                                    ->columnSpan(2),

                                TextInput::make('row_total')
                                    ->label(__('asset_funds.asset_fund_voucher_items.fields.row_total'))
                                    ->numeric()
                                    ->prefix('SAR')
                                    ->default(0)
                                    ->readOnly()
                                    ->columnSpan(2)
                                    ->extraAttributes(['class' => 'font-bold text-primary-600']),
                            ])
                            ->addActionLabel(__('asset_funds.asset_fund_voucher_items.actions.add_item'))
                            ->defaultItems(1)
                            ->live()
                            ->afterStateUpdated(fn (callable $get, callable $set) => self::recalcTotal($get, $set)),

                        TextInput::make('total_amount')
                            ->label(__('asset_funds.asset_fund_vouchers.fields.total_amount'))
                            ->helperText(__('asset_funds.asset_fund_vouchers.helpers.total_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->readOnly()
                            ->extraAttributes(['class' => 'text-xl font-bold']),
                    ]),
            ]);
    }

    private static function recalcRow(callable $get, callable $set): void
    {
        $subTotal = (float) ($get('sub_total') ?? 0);
        $extra1   = (float) ($get('extra_1')   ?? 0);
        $extra2   = (float) ($get('extra_2')   ?? 0);

        $rowTotal = round($subTotal + $extra1 + $extra2, 2);
        $set('row_total', $rowTotal);

        // Update grand total on the parent voucher form immediately
        $items = $get('../../items') ?? [];
        $grandTotal = 0;

        foreach ($items as $item) {
            $grandTotal += (float) ($item['sub_total'] ?? 0) + (float) ($item['extra_1'] ?? 0) + (float) ($item['extra_2'] ?? 0);
        }

        $set('../../total_amount', round($grandTotal, 2));
    }

    private static function recalcTotal(callable $get, callable $set): void
    {
        $items = $get('items') ?? [];
        $total = 0;

        foreach ($items as $item) {
            $total += (float) ($item['sub_total'] ?? 0) + (float) ($item['extra_1'] ?? 0) + (float) ($item['extra_2'] ?? 0);
        }

        $set('total_amount', round($total, 2));
    }
}
