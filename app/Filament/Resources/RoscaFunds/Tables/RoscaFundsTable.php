<?php

namespace App\Filament\Resources\RoscaFunds\Tables;

use App\Filament\Components\OrgSelect;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RoscaFundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fund_number')
                    ->label(__('funds.rosca_funds.fields.fund_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('fund_name')
                    ->label(__('funds.rosca_funds.fields.fund_name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                OrgSelect::tableColumnCompany('company.trade_name', __('funds.rosca_funds.fields.company')),

                TextColumn::make('members_count')
                    ->label(__('funds.rosca_funds.fields.members_count'))
                    ->sortable(),

                TextColumn::make('monthly_installment')
                    ->label(__('funds.rosca_funds.fields.monthly_installment'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('total_pool_amount')
                    ->label(__('funds.rosca_funds.fields.total_pool_amount'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('allocation_method')
                    ->label(__('funds.rosca_funds.fields.allocation_method'))
                    ->badge(),

                TextColumn::make('start_date')
                    ->label(__('funds.rosca_funds.fields.start_date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('funds.rosca_funds.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active' => __('funds.enums.general_status.active'),
                        'completed' => __('funds.enums.general_status.completed'),
                        'closed' => __('funds.enums.general_status.closed'),
                        default => $state,
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'info',
                        'closed' => 'gray',
                        default => 'warning',
                    }),
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
