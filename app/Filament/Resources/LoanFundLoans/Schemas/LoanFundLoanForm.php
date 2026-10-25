<?php

namespace App\Filament\Resources\LoanFundLoans\Schemas;

use App\Models\LoanFund;
use App\Models\LoanFundMember;
use App\Services\LoanFundLimitService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LoanFundLoanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('funds.loan_fund_loans.sections.loan_info'))
                    ->icon('heroicon-o-document-currency-dollar')
                    ->columns(3)
                    ->schema([
                        TextInput::make('loan_number')
                            ->label(__('funds.loan_fund_loans.fields.loan_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        Select::make('loan_fund_id')
                            ->label(__('funds.loan_fund_loans.fields.fund'))
                            ->relationship('loanFund', 'fund_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('loan_fund_member_id', null)),

                        Select::make('loan_fund_member_id')
                            ->label(__('funds.loan_fund_loans.fields.member'))
                            ->options(function (callable $get) {
                                $fundId = $get('loan_fund_id');
                                if (!$fundId) {
                                    return LoanFundMember::where('status', 'active')->pluck('full_name', 'id');
                                }
                                return LoanFundMember::where('loan_fund_id', $fundId)
                                    ->where('status', 'active')
                                    ->pluck('full_name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $member = LoanFundMember::find($state);
                                    if ($member) {
                                        $set('loan_fund_id', $member->loan_fund_id);
                                    }
                                }
                            }),

                        DatePicker::make('loan_date')
                            ->label(__('funds.loan_fund_loans.fields.loan_date'))
                            ->default(now())
                            ->required(),

                        DatePicker::make('start_due_date')
                            ->label(__('funds.loan_fund_loans.fields.start_due_date'))
                            ->default(now()->addMonth())
                            ->required(),

                        LoanFundLimitService::applyLoanAmountLimit(
                            TextInput::make('loan_amount')
                                ->label(__('funds.loan_fund_loans.fields.loan_amount'))
                                ->numeric()
                                ->prefix('SAR')
                                ->default(0)
                                ->required()
                                ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                                ->reactive()
                                ->afterStateUpdated(fn (callable $set, callable $get) => static::recalculateLoan($set, $get))
                        ),

                        LoanFundLimitService::applyInstallmentsLimit(
                            TextInput::make('installments_count')
                                ->label(__('funds.loan_fund_loans.fields.installments_count'))
                                ->numeric()
                                ->default(1)
                                ->required()
                                ->dehydrateStateUsing(fn ($state) => max(1, (int)($state ?: 1)))
                                ->reactive()
                                ->afterStateUpdated(fn (callable $set, callable $get) => static::recalculateLoan($set, $get))
                        ),

                        TextInput::make('monthly_installment')
                            ->label(__('funds.loan_fund_loans.fields.monthly_installment'))
                            ->numeric()
                            ->prefix('SAR')
                            ->disabled()
                            ->dehydrated()
                            ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                            ->helperText(__('funds.loan_fund_loans.helpers.monthly_installment')),

                        Select::make('status')
                            ->label(__('funds.loan_fund_loans.fields.status'))
                            ->options([
                                'active'    => __('funds.enums.general_status.active'),
                                'completed' => __('funds.enums.general_status.completed'),
                                'defaulted' => __('funds.enums.general_status.defaulted'),
                            ])
                            ->default('active')
                            ->required(),
                    ]),

                Section::make(__('funds.loan_fund_loans.sections.repayment_schedule'))
                    ->icon('heroicon-o-calculator')
                    ->columns(3)
                    ->schema([
                        TextInput::make('paid_amount')
                            ->label(__('funds.loan_fund_loans.fields.paid_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set, callable $get) => static::recalculateLoan($set, $get)),

                        TextInput::make('remaining_amount')
                            ->label(__('funds.loan_fund_loans.fields.remaining_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->disabled()
                            ->dehydrated()
                            ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                            ->helperText(__('funds.loan_fund_loans.helpers.remaining_amount')),

                        Toggle::make('is_closed')
                            ->label(__('funds.loan_fund_loans.fields.is_closed'))
                            ->default(false)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('closure_date', now()->toDateString());
                                    $set('status', 'completed');
                                }
                            }),

                        DatePicker::make('closure_date')
                            ->label(__('funds.loan_fund_loans.fields.closure_date'))
                            ->visible(fn (callable $get) => (bool)$get('is_closed')),

                        Textarea::make('notes')
                            ->label(__('funds.loan_fund_loans.fields.notes'))
                            ->columnSpanFull()
                            ->rows(2),
                    ]),
            ]);
    }

    public static function recalculateLoan(callable $set, callable $get): void
    {
        $amount = (float)($get('loan_amount') ?? 0);
        $count = max(1, (int)($get('installments_count') ?? 1));
        $paid = (float)($get('paid_amount') ?? 0);

        $monthly = round($amount / $count, 2);
        $remaining = max(0, $amount - $paid);

        $set('monthly_installment', $monthly);
        $set('remaining_amount', $remaining);

        if ($remaining <= 0 && $amount > 0) {
            $set('status', 'completed');
            $set('is_closed', true);
            $set('closure_date', now()->toDateString());
        }
    }
}
