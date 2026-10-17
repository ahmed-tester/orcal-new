<?php

namespace App\Filament\Resources\ViolationTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ViolationTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')
                    ->label(__('violations.types.fields.category'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('name')
                    ->label(__('violations.types.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                IconColumn::make('is_active')
                    ->label(__('violations.types.fields.is_active'))
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->label(__('violations.types.fields.sort_order'))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('general.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('violation_category_id')
                    ->label(__('violations.types.fields.category'))
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label(__('violations.types.fields.is_active')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
