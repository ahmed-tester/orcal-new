<?php

namespace App\Filament\Resources\LoanFundMembers\Schemas;

use App\Enums\FundPaymentStatus;
use App\Models\Employee;
use App\Models\LoanFund;
use App\Services\CompanyFormService;
use App\Services\PhoneService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LoanFundMemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('funds.loan_fund_members.sections.member_info'))
                    ->icon('heroicon-o-user')
                    ->columns(3)
                    ->schema([
                        Select::make('loan_fund_id')
                            ->label(__('funds.loan_fund_members.fields.fund'))
                            ->relationship('loanFund', 'fund_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $fund = LoanFund::find($state);
                                    if ($fund) {
                                        $set('installments_count', $fund->default_installments_count);
                                    }
                                }
                            }),

                        Select::make('member_type')
                            ->label(__('funds.loan_fund_members.fields.member_type'))
                            ->options([
                                'external' => __('funds.enums.member_type.external'),
                                'employee' => __('funds.enums.member_type.employee'),
                            ])
                            ->default('external')
                            ->required()
                            ->reactive(),

                        CompanyFormService::makeEmployeeSelect(
                            label: __('funds.loan_fund_members.fields.employee'),
                            required: false,
                        )
                            ->visible(fn (callable $get) => $get('member_type') === 'employee')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $employee = Employee::find($state);
                                    if ($employee) {
                                        $set('full_name', $employee->user?->name);
                                        $set('phone', $employee->user?->phone ?? $employee->phone);
                                        $set('national_id', $employee->saudiDetail?->national_id ?? $employee->foreignDetail?->iqama_number);
                                    }
                                }
                            }),

                        TextInput::make('full_name')
                            ->label(__('funds.loan_fund_members.fields.full_name'))
                            ->required(),

                        TextInput::make('national_id')
                            ->label(__('funds.loan_fund_members.fields.national_id')),

                        PhoneService::makeFormField('phone', __('funds.loan_fund_members.fields.phone')),

                        DatePicker::make('joined_date')
                            ->label(__('funds.loan_fund_members.fields.joined_date'))
                            ->default(now()),

                        Select::make('status')
                            ->label(__('funds.loan_fund_members.fields.status'))
                            ->options([
                                'active' => __('funds.enums.general_status.active'),
                                'inactive' => __('funds.enums.general_status.inactive'),
                            ])
                            ->default('active')
                            ->required(),

                        TextInput::make('address')
                            ->label(__('funds.loan_fund_members.fields.address'))
                            ->columnSpanFull(),
                    ]),

                Section::make(__('funds.loan_fund_members.sections.financial_info'))
                    ->icon('heroicon-o-calculator')
                    ->columns(3)
                    ->schema([
                        TextInput::make('installments_count')
                            ->label(__('funds.loan_fund_members.fields.installments_count'))
                            ->numeric()
                            ->default(12)
                            ->minValue(1)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => max(1, (int) ($state ?: 12)))
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set, callable $get) => static::recalculate($set, $get)),

                        TextInput::make('monthly_installment')
                            ->label(__('funds.loan_fund_members.fields.monthly_installment'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set, callable $get) => static::recalculate($set, $get)),

                        TextInput::make('total_annual_amount')
                            ->label(__('funds.loan_fund_members.fields.total_annual_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->disabled()
                            ->dehydrated()
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->helperText(__('funds.loan_fund_members.helpers.total_annual_amount')),

                        TextInput::make('paid_amount')
                            ->label(__('funds.loan_fund_members.fields.paid_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set, callable $get) => static::recalculate($set, $get)),

                        TextInput::make('remaining_amount')
                            ->label(__('funds.loan_fund_members.fields.remaining_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->disabled()
                            ->dehydrated()
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                            ->helperText(__('funds.loan_fund_members.helpers.remaining_amount')),

                        Select::make('payment_status')
                            ->label(__('funds.loan_fund_members.fields.payment_status'))
                            ->options(FundPaymentStatus::class)
                            ->default(FundPaymentStatus::UNPAID->value)
                            ->required(),

                        Textarea::make('notes')
                            ->label(__('funds.loan_fund_members.fields.notes'))
                            ->columnSpanFull()
                            ->rows(2),
                    ]),
            ]);
    }

    public static function recalculate(callable $set, callable $get): void
    {
        $count = max(1, (int) ($get('installments_count') ?? 12));
        $monthly = (float) ($get('monthly_installment') ?? 0);
        $total = round($count * $monthly, 2);
        $paid = (float) ($get('paid_amount') ?? 0);
        $remaining = max(0, $total - $paid);

        $set('total_annual_amount', $total);
        $set('remaining_amount', $remaining);

        if ($paid >= $total && $total > 0) {
            $set('payment_status', FundPaymentStatus::PAID->value);
        } elseif ($paid > 0) {
            $set('payment_status', FundPaymentStatus::PARTIAL->value);
        } else {
            $set('payment_status', FundPaymentStatus::UNPAID->value);
        }
    }
}
