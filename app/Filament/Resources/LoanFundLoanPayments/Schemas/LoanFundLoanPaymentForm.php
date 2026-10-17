<?php

namespace App\Filament\Resources\LoanFundLoanPayments\Schemas;

use App\Models\LoanFundLoan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LoanFundLoanPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('funds.loan_fund_loan_payments.sections.payment_info'))
                    ->icon('heroicon-o-receipt-percent')
                    ->columns(3)
                    ->schema([
                        TextInput::make('voucher_number')
                            ->label(__('funds.loan_fund_loan_payments.fields.voucher_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        Select::make('loan_fund_loan_id')
                            ->label(__('funds.loan_fund_loan_payments.fields.loan'))
                            ->options(function () {
                                return LoanFundLoan::where('is_closed', false)
                                    ->with('member')
                                    ->get()
                                    ->mapWithKeys(function ($loan) {
                                        $name = $loan->member?->full_name ?? "Member #{$loan->loan_fund_member_id}";
                                        return [$loan->id => "{$loan->loan_number} - {$name} (Remaining: {$loan->remaining_amount} SAR)"];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $loan = LoanFundLoan::find($state);
                                    if ($loan) {
                                        $set('loan_fund_member_id', $loan->loan_fund_member_id);
                                        $set('scheduled_amount', $loan->monthly_installment);
                                        $set('collected_amount', $loan->monthly_installment);
                                        $remaining = max(0, (float)$loan->remaining_amount - (float)$loan->monthly_installment);
                                        $set('remaining_balance', $remaining);
                                        if ($remaining <= 0) {
                                            $set('is_closed', true);
                                        }
                                    }
                                }
                            }),

                        DatePicker::make('installment_date')
                            ->label(__('funds.loan_fund_loan_payments.fields.installment_date'))
                            ->default(now())
                            ->required(),

                        TextInput::make('installment_order')
                            ->label(__('funds.loan_fund_loan_payments.fields.installment_order'))
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => max(1, (int)($state ?: 1))),

                        TextInput::make('scheduled_amount')
                            ->label(__('funds.loan_fund_loan_payments.fields.scheduled_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0)),

                        TextInput::make('collected_amount')
                            ->label(__('funds.loan_fund_loan_payments.fields.collected_amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0))
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $loanId = $get('loan_fund_loan_id');
                                if ($loanId) {
                                    $loan = LoanFundLoan::find($loanId);
                                    if ($loan) {
                                        $remaining = max(0, (float)$loan->remaining_amount - (float)$state);
                                        $set('remaining_balance', $remaining);
                                        if ($remaining <= 0) {
                                            $set('is_closed', true);
                                        }
                                    }
                                }
                            }),

                        TextInput::make('remaining_balance')
                            ->label(__('funds.loan_fund_loan_payments.fields.remaining_balance'))
                            ->numeric()
                            ->prefix('SAR')
                            ->disabled()
                            ->dehydrated()
                            ->helperText(__('funds.loan_fund_loan_payments.helpers.remaining_balance')),

                        Toggle::make('is_closed')
                            ->label(__('funds.loan_fund_loan_payments.fields.is_closed'))
                            ->default(false),

                        Textarea::make('notes')
                            ->label(__('funds.loan_fund_loan_payments.fields.notes'))
                            ->columnSpanFull()
                            ->rows(2),
                    ]),
            ]);
    }
}
