<?php

namespace App\Filament\Resources\RoscaFundMembers\Schemas;

use App\Models\Employee;
use App\Models\RoscaFund;
use App\Services\CompanyFormService;
// use App\Services\FundFormService;
use App\Services\PhoneService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class RoscaFundMemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('funds.rosca_fund_members.sections.member_info'))
                    ->icon('heroicon-o-user')
                    ->columns(3)
                    ->schema([
                        Select::make('rosca_fund_id')
                            ->label(__('funds.rosca_fund_members.fields.fund'))
                            ->relationship('roscaFund', 'fund_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $fund = RoscaFund::find($state);
                                    if ($fund) {
                                        $set('installments_count', $fund->members_count);
                                        $set('monthly_installment', $fund->monthly_installment);
                                        $set('total_final_amount', $fund->total_pool_amount);
                                    }
                                }
                            }),

                        Select::make('member_type')
                            ->label(__('funds.rosca_fund_members.fields.member_type'))
                            ->options([
                                'external' => __('funds.enums.member_type.external'),
                                'employee' => __('funds.enums.member_type.employee'),
                            ])
                            ->default('external')
                            ->required()
                            ->reactive(),

                        CompanyFormService::makeEmployeeSelect(
                            name: 'employee_id',
                            label: __('funds.rosca_fund_members.fields.employee'),
                            required: false,
                            saudiOnly: true,
                            ignoreCompanyScope: true,
                        )
                            // FundFormService::makeSaudiEmployeeSelect(
                            //     name: 'employee_id',
                            //     label: __('funds.rosca_fund_members.fields.employee'),
                            //     required: false,
                            // )
                            // Select::make('employee_id')
                            //     ->label(__('funds.rosca_fund_members.fields.employee'))
                            //     ->relationship(
                            //         name: 'employee',
                            //         modifyQueryUsing: fn (Builder $query) => $query
                            //             ->withoutGlobalScopes()
                            //             ->with(['user', 'details'])
                            //             ->where('status', 'active')
                            //             ->saudi()
                            //     )
                            //     ->getOptionLabelFromRecordUsing(
                            //         fn ($record) => ($record->user?->name ?? "موظف #{$record->id}").($record->employee_number ? " ({$record->employee_number})" : '')
                            //     )
                            //     ->searchable()
                            //     ->preload()
                            //     ->nullable()
                            ->visible(fn (callable $get) => $get('member_type') === 'employee')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $employee = Employee::find($state);
                                    if ($employee) {
                                        $set('full_name', $employee->user?->name);
                                        $set('phone', $employee->user?->phone ?? $employee->phone);
                                        // $set('national_id', $employee->saudiDetail?->national_id ?? $employee->foreignDetail?->iqama_number);
                                        $set('national_id', $employee->details?->national_id ?? $employee->saudiDetail?->national_id);
                                    }
                                }
                            }),

                        TextInput::make('full_name')
                            ->label(__('funds.rosca_fund_members.fields.full_name'))
                            ->required(),

                        TextInput::make('national_id')
                            ->label(__('funds.rosca_fund_members.fields.national_id')),

                        PhoneService::makeFormField('phone', __('funds.rosca_fund_members.fields.phone')),

                        TextInput::make('address')
                            ->label(__('funds.rosca_fund_members.fields.address'))
                            ->columnSpanFull(),
                    ]),

                Section::make(__('funds.rosca_fund_members.sections.financial_turn'))
                    ->icon('heroicon-o-calendar-days')
                    ->columns(3)
                    ->schema([
                        TextInput::make('turn_order')
                            ->label(__('funds.rosca_fund_members.fields.turn_order'))
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => max(1, (int) ($state ?: 1))),

                        DatePicker::make('payout_month')
                            ->label(__('funds.rosca_fund_members.fields.payout_month')),

                        TextInput::make('installments_count')
                            ->label(__('funds.rosca_fund_members.fields.installments_count'))
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => max(1, (int) ($state ?: 1)))
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set, callable $get) => static::recalculate($set, $get)),

                        TextInput::make('monthly_installment')
                            ->label(__('funds.rosca_fund_members.fields.monthly_installment'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set, callable $get) => static::recalculate($set, $get)),

                        TextInput::make('total_final_amount')
                            ->label(__('funds.rosca_fund_members.fields.total_final_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->disabled()
                            ->dehydrated()
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->helperText(__('funds.rosca_fund_members.helpers.total_final_amount')),

                        TextInput::make('paid_amount')
                            ->label(__('funds.rosca_fund_members.fields.paid_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set, callable $get) => static::recalculate($set, $get)),

                        TextInput::make('remaining_amount')
                            ->label(__('funds.rosca_fund_members.fields.remaining_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->disabled()
                            ->dehydrated()
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0)),

                        Toggle::make('has_received_payout')
                            ->label(__('funds.rosca_fund_members.fields.has_received_payout'))
                            ->default(false)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('payout_date', now()->toDateString());
                                }
                            }),

                        DatePicker::make('payout_date')
                            ->label(__('funds.rosca_fund_members.fields.payout_date'))
                            ->visible(fn (callable $get) => (bool) $get('has_received_payout')),

                        Select::make('status')
                            ->label(__('funds.rosca_fund_members.fields.status'))
                            ->options([
                                'active' => __('funds.enums.general_status.active'),
                                'completed' => __('funds.enums.general_status.completed'),
                                'dropped' => __('funds.enums.general_status.dropped'),
                            ])
                            ->default('active')
                            ->required(),
                    ]),
            ]);
    }

    public static function recalculate(callable $set, callable $get): void
    {
        $count = max(1, (int) ($get('installments_count') ?? 1));
        $monthly = (float) ($get('monthly_installment') ?? 0);
        $total = round($count * $monthly, 2);
        $paid = (float) ($get('paid_amount') ?? 0);
        $remaining = max(0, $total - $paid);

        $set('total_final_amount', $total);
        $set('remaining_amount', $remaining);
    }
}
