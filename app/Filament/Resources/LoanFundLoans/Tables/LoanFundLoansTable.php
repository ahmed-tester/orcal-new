<?php

namespace App\Filament\Resources\LoanFundLoans\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LoanFundLoansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('loan_number')
                    ->label(__('funds.loan_fund_loans.fields.loan_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('member.full_name')
                    ->label(__('funds.loan_fund_loans.fields.member'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('loanFund.fund_name')
                    ->label(__('funds.loan_fund_loans.fields.fund'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('loan_amount')
                    ->label(__('funds.loan_fund_loans.fields.loan_amount'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('monthly_installment')
                    ->label(__('funds.loan_fund_loans.fields.monthly_installment'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('paid_amount')
                    ->label(__('funds.loan_fund_loans.fields.paid_amount'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('remaining_amount')
                    ->label(__('funds.loan_fund_loans.fields.remaining_amount'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('funds.loan_fund_loans.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active' => __('funds.enums.general_status.active'),
                        'completed' => __('funds.enums.general_status.completed'),
                        'defaulted' => __('funds.enums.general_status.defaulted'),
                        default => $state,
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'completed' => 'success',
                        'active' => 'warning',
                        'defaulted' => 'danger',
                        default => 'gray',
                    }),

                IconColumn::make('is_closed')
                    ->label(__('funds.loan_fund_loans.fields.is_closed'))
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('loan_fund_id')
                    ->label(__('funds.loan_fund_loans.fields.fund'))
                    ->relationship('loanFund', 'fund_name'),

                SelectFilter::make('status')
                    ->label(__('funds.loan_fund_loans.fields.status'))
                    ->options([
                        'active' => __('funds.enums.general_status.active'),
                        'completed' => __('funds.enums.general_status.completed'),
                        'defaulted' => __('funds.enums.general_status.defaulted'),
                    ]),
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
