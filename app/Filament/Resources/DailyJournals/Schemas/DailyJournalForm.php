<?php

namespace App\Filament\Resources\DailyJournals\Schemas;

use App\Filament\Components\OrgSelect;
use App\Models\DailyJournal;
use App\Models\Employee;
use App\Services\PhoneService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DailyJournalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([

                Section::make(__('finance.daily_journals.sections.entry_info'))
                    ->description(__('finance.daily_journals.sections.entry_info_desc'))
                    ->icon('heroicon-o-calculator')
                    ->columns(3)
                    ->schema([
                        TextInput::make('entry_number')
                            ->label(__('finance.daily_journals.fields.entry_number'))
                            ->placeholder(__('finance.daily_journals.placeholders.auto_entry_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        DatePicker::make('entry_date')
                            ->label(__('finance.daily_journals.fields.entry_date'))
                            ->default(now())
                            ->required(),

                        OrgSelect::company('company_id', __('finance.daily_journals.fields.company'), ['branch_id', 'recorded_by', 'recorder_phone']),

                        OrgSelect::branch('branch_id', __('finance.daily_journals.fields.branch')),

                        OrgSelect::employee(
                            name: 'recorded_by',
                            label: __('finance.daily_journals.fields.recorded_by'),
                            autoFillPhone: 'recorder_phone',
                        ),

                        PhoneService::makeFormField('recorder_phone', __('finance.daily_journals.fields.recorder_phone'))
                            ->disabled()
                            ->dehydrated(false)
                            ->default(function () {
                                $empId = auth()->user()?->employee?->id;
                                return $empId ? Employee::with('user')->find($empId)?->user?->phone : auth()->user()?->phone;
                            }),
                    ]),

                Section::make()
                    ->schema([
                        Toggle::make('is_detailed')
                            ->label(__('finance.daily_journals.fields.is_detailed'))
                            ->helperText(__('finance.daily_journals.helpers.is_detailed'))
                            ->onIcon('heroicon-m-list-bullet')
                            ->offIcon('heroicon-m-minus')
                            ->onColor('primary')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (! $state) {
                                    foreach ([
                                        'sales_cash', 'sales_network', 'sales_deferred', 'sales_other',
                                        'purchases_cash', 'purchases_network', 'purchases_credit', 'purchases_debit', 'expenses_other',
                                    ] as $field) {
                                        $set($field, null);
                                    }
                                }
                            }),
                    ]),

                Section::make(__('finance.daily_journals.sections.sales_summary'))
                    ->description(__('finance.daily_journals.sections.sales_summary_desc'))
                    ->icon('heroicon-o-arrow-trending-up')
                    ->columns(3)
                    ->visible(fn ($get) => ! $get('is_detailed'))
                    ->schema([
                        TextInput::make('sales')
                            ->label(__('finance.daily_journals.fields.sales'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $set('net_amount', round((float)($state ?? 0) - (float)($get('purchases') ?? 0), 2));
                            }),

                        TextInput::make('purchases')
                            ->label(__('finance.daily_journals.fields.purchases'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $set('net_amount', round((float)($get('sales') ?? 0) - (float)($state ?? 0), 2));
                            }),

                        TextInput::make('net_amount')
                            ->label(__('finance.daily_journals.fields.net_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->disabled()
                            ->dehydrated()
                            ->default(0)
                            ->helperText(__('finance.daily_journals.helpers.net_amount')),
                    ]),

                Section::make(__('finance.daily_journals.sections.sales_detail'))
                    ->description(__('finance.daily_journals.sections.sales_detail_desc'))
                    ->icon('heroicon-o-arrow-trending-up')
                    ->columns(2)
                    ->visible(fn ($get) => (bool) $get('is_detailed'))
                    ->schema([
                        TextInput::make('sales_cash')
                            ->label(__('finance.daily_journals.fields.sales_cash'))
                            ->numeric()->prefix('SAR')->default(0)->minValue(0)
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::recalcDetailed($set, $get)),

                        TextInput::make('sales_network')
                            ->label(__('finance.daily_journals.fields.sales_network'))
                            ->numeric()->prefix('SAR')->default(0)->minValue(0)
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::recalcDetailed($set, $get)),

                        TextInput::make('sales_deferred')
                            ->label(__('finance.daily_journals.fields.sales_deferred'))
                            ->numeric()->prefix('SAR')->default(0)->minValue(0)
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::recalcDetailed($set, $get)),

                        TextInput::make('sales_other')
                            ->label(__('finance.daily_journals.fields.sales_other'))
                            ->numeric()->prefix('SAR')->default(0)->minValue(0)
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::recalcDetailed($set, $get)),

                        TextInput::make('sales')
                            ->label(__('finance.daily_journals.fields.sales_total'))
                            ->numeric()->prefix('SAR')
                            ->disabled()->dehydrated()
                            ->default(0)
                            ->columnSpanFull()
                            ->helperText(__('finance.daily_journals.helpers.sales_total')),
                    ]),

                Section::make(__('finance.daily_journals.sections.purchases_detail'))
                    ->description(__('finance.daily_journals.sections.purchases_detail_desc'))
                    ->icon('heroicon-o-arrow-trending-down')
                    ->columns(2)
                    ->visible(fn ($get) => (bool) $get('is_detailed'))
                    ->schema([
                        TextInput::make('purchases_cash')
                            ->label(__('finance.daily_journals.fields.purchases_cash'))
                            ->numeric()->prefix('SAR')->default(0)->minValue(0)
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::recalcDetailed($set, $get)),

                        TextInput::make('purchases_network')
                            ->label(__('finance.daily_journals.fields.purchases_network'))
                            ->numeric()->prefix('SAR')->default(0)->minValue(0)
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::recalcDetailed($set, $get)),

                        TextInput::make('purchases_credit')
                            ->label(__('finance.daily_journals.fields.purchases_credit'))
                            ->numeric()->prefix('SAR')->default(0)->minValue(0)
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::recalcDetailed($set, $get)),

                        TextInput::make('purchases_debit')
                            ->label(__('finance.daily_journals.fields.purchases_debit'))
                            ->numeric()->prefix('SAR')->default(0)->minValue(0)
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::recalcDetailed($set, $get)),

                        TextInput::make('expenses_other')
                            ->label(__('finance.daily_journals.fields.expenses_other'))
                            ->numeric()->prefix('SAR')->default(0)->minValue(0)
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) => static::recalcDetailed($set, $get)),

                        TextInput::make('purchases')
                            ->label(__('finance.daily_journals.fields.purchases_total'))
                            ->numeric()->prefix('SAR')
                            ->disabled()->dehydrated()
                            ->default(0)
                            ->columnSpanFull()
                            ->helperText(__('finance.daily_journals.helpers.purchases_total')),
                    ]),

                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('net_amount')
                            ->label(__('finance.daily_journals.fields.net_amount'))
                            ->numeric()->prefix('SAR')
                            ->disabled()->dehydrated()
                            ->default(0)
                            ->visible(fn ($get) => (bool) $get('is_detailed'))
                            ->helperText(__('finance.daily_journals.helpers.net_amount')),

                        Textarea::make('notes')
                            ->label(__('finance.daily_journals.fields.notes'))
                            ->placeholder(__('finance.daily_journals.placeholders.notes'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }


    protected static function recalcDetailed(callable $set, callable $get): void
    {
        $sales = array_sum([
            (float) ($get('sales_cash')     ?? 0),
            (float) ($get('sales_network')  ?? 0),
            (float) ($get('sales_deferred') ?? 0),
            (float) ($get('sales_other')    ?? 0),
        ]);

        $purchases = array_sum([
            (float) ($get('purchases_cash')    ?? 0),
            (float) ($get('purchases_network') ?? 0),
            (float) ($get('purchases_credit')  ?? 0),
            (float) ($get('purchases_debit')   ?? 0),
            (float) ($get('expenses_other')    ?? 0),
        ]);

        $set('sales',      round($sales,     2));
        $set('purchases',  round($purchases, 2));
        $set('net_amount', round($sales - $purchases, 2));
    }
}
