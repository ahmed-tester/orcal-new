<?php

namespace App\Filament\Resources\InstallmentTypes\Tables;

use App\Enums\InstallmentCategory;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InstallmentTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading(fn () => __('finance.installment_types.table_heading', [
                'count' => \App\Filament\Resources\InstallmentTypes\InstallmentTypeResource::getEloquentQuery()->count(),
            ]))
            ->columns([
                TextColumn::make('category')
                    ->label(__('finance.installment_types.fields.category'))
                    ->badge(),

                TextColumn::make('name')
                    ->label(__('finance.installment_types.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('company.trade_name')
                    ->label(__('finance.installment_types.fields.company'))
                    ->default(__('finance.installment_types.general_to_all'))
                    ->badge()
                    ->color('gray'),

                TextColumn::make('status')
                    ->label(__('finance.installment_types.fields.status'))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->label(__('finance.installment_types.fields.category'))
                    ->options(InstallmentCategory::class),

                SelectFilter::make('status')
                    ->label(__('finance.installment_types.fields.status'))
                    ->options(\App\Enums\GeneralStatus::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
