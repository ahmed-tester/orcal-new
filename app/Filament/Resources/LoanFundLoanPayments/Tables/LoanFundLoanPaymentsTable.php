<?php

namespace App\Filament\Resources\LoanFundLoanPayments\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoanFundLoanPaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('voucher_number')
                    ->label(__('funds.loan_fund_loan_payments.fields.voucher_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('member.full_name')
                    ->label(__('funds.loan_fund_loan_payments.fields.member'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('installment_date')
                    ->label(__('funds.loan_fund_loan_payments.fields.installment_date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('installment_order')
                    ->label(__('funds.loan_fund_loan_payments.fields.installment_order'))
                    ->sortable(),

                TextColumn::make('collected_amount')
                    ->label(__('funds.loan_fund_loan_payments.fields.collected_amount'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('remaining_balance')
                    ->label(__('funds.loan_fund_loan_payments.fields.remaining_balance'))
                    ->money('SAR')
                    ->sortable(),

                IconColumn::make('is_closed')
                    ->label(__('funds.loan_fund_loan_payments.fields.is_closed'))
                    ->boolean(),
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
