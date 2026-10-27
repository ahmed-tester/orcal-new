<?php

namespace App\Filament\Resources\LoanFunds\Tables;

// use App\Filament\Components\OrgSelect;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoanFundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fund_number')
                    ->label(__('funds.loan_funds.fields.fund_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('fund_name')
                    ->label(__('funds.loan_funds.fields.fund_name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                // OrgSelect::tableColumnCompany('company.trade_name', __('funds.loan_funds.fields.company')),

                TextColumn::make('target_amount')
                    ->label(__('funds.loan_funds.fields.target_amount'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('max_loan_limit')
                    ->label(__('funds.loan_funds.fields.max_loan_limit'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('members_count')
                    ->label(__('funds.loan_fund_members.plural'))
                    ->counts('members')
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->label(__('funds.loan_funds.fields.status'))
                    ->badge(),
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
