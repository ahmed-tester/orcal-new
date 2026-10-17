<?php

namespace App\Filament\Resources\LoanFundMemberPayments\Tables;

use App\Enums\FundPaymentStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LoanFundMemberPaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('voucher_number')
                    ->label(__('funds.loan_fund_member_payments.fields.voucher_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('member.full_name')
                    ->label(__('funds.loan_fund_member_payments.fields.member'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('loanFund.fund_name')
                    ->label(__('funds.loan_fund_member_payments.fields.fund'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('payment_date')
                    ->label(__('funds.loan_fund_member_payments.fields.payment_date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('period_month')
                    ->label(__('funds.loan_fund_member_payments.fields.period_month'))
                    ->date('Y-m')
                    ->sortable(),

                TextColumn::make('installment_order')
                    ->label(__('funds.loan_fund_member_payments.fields.installment_order'))
                    ->sortable(),

                TextColumn::make('amount')
                    ->label(__('funds.loan_fund_member_payments.fields.amount'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label(__('funds.loan_fund_member_payments.fields.payment_method'))
                    ->badge(),

                TextColumn::make('payment_status')
                    ->label(__('funds.loan_fund_member_payments.fields.payment_status'))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('loan_fund_id')
                    ->label(__('funds.loan_fund_member_payments.fields.fund'))
                    ->relationship('loanFund', 'fund_name'),

                SelectFilter::make('payment_status')
                    ->label(__('funds.loan_fund_member_payments.fields.payment_status'))
                    ->options(FundPaymentStatus::class),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
