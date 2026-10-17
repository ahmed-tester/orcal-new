<?php

namespace App\Filament\Resources\LoanFundMemberPayments\Schemas;

use App\Enums\FundPaymentStatus;
use App\Models\LoanFund;
use App\Models\LoanFundMember;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LoanFundMemberPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('funds.loan_fund_member_payments.sections.payment_info'))
                    ->icon('heroicon-o-banknotes')
                    ->columns(3)
                    ->schema([
                        TextInput::make('voucher_number')
                            ->label(__('funds.loan_fund_member_payments.fields.voucher_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        Select::make('loan_fund_id')
                            ->label(__('funds.loan_fund_member_payments.fields.fund'))
                            ->relationship('loanFund', 'fund_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('loan_fund_member_id', null)),

                        Select::make('loan_fund_member_id')
                            ->label(__('funds.loan_fund_member_payments.fields.member'))
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
                                        $set('amount', $member->monthly_installment);
                                    }
                                }
                            }),

                        DatePicker::make('payment_date')
                            ->label(__('funds.loan_fund_member_payments.fields.payment_date'))
                            ->default(now())
                            ->required(),

                        DatePicker::make('period_month')
                            ->label(__('funds.loan_fund_member_payments.fields.period_month'))
                            ->default(now())
                            ->required(),

                        TextInput::make('installment_order')
                            ->label(__('funds.loan_fund_member_payments.fields.installment_order'))
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => max(1, (int)($state ?: 1))),

                        TextInput::make('amount')
                            ->label(__('funds.loan_fund_member_payments.fields.amount'))
                            ->numeric()
                            ->prefix('SAR')
                            ->default(0)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0)),

                        Select::make('payment_method')
                            ->label(__('funds.loan_fund_member_payments.fields.payment_method'))
                            ->options([
                                'cash'              => __('funds.loan_fund_member_payments.methods.cash'),
                                'bank_transfer'     => __('funds.loan_fund_member_payments.methods.bank_transfer'),
                                'payroll_deduction' => __('funds.loan_fund_member_payments.methods.payroll_deduction'),
                            ])
                            ->default('cash')
                            ->required(),

                        Select::make('payment_status')
                            ->label(__('funds.loan_fund_member_payments.fields.payment_status'))
                            ->options(FundPaymentStatus::class)
                            ->default(FundPaymentStatus::PAID->value)
                            ->required(),

                        Textarea::make('notes')
                            ->label(__('funds.loan_fund_member_payments.fields.notes'))
                            ->columnSpanFull()
                            ->rows(2),
                    ]),
            ]);
    }
}
