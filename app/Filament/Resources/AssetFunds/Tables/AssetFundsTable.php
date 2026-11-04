<?php

namespace App\Filament\Resources\AssetFunds\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetFundsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fund_number')
                    ->label(__('asset_funds.asset_funds.fields.fund_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('fund_name')
                    ->label(__('asset_funds.asset_funds.fields.fund_name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('established_date')
                    ->label(__('asset_funds.asset_funds.fields.established_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('manager_name')
                    ->label(__('asset_funds.asset_funds.fields.manager_name'))
                    ->getStateUsing(fn ($record) => $record->managerEmployee?->user?->name ?? $record->manager_name ?? '—')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('balance')
                    ->label(__('asset_funds.asset_funds.fields.balance'))
                    ->getStateUsing(fn ($record) => $record->balance)
                    ->money('SAR')
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->weight('bold'),

                TextColumn::make('vouchers_count')
                    ->label(__('asset_funds.asset_funds.fields.vouchers_count'))
                    ->counts('vouchers')
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->label(__('asset_funds.asset_funds.fields.status'))
                    ->badge(),
            ])
            ->defaultSort('created_at', 'desc')
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

