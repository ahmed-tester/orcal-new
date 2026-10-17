<?php

namespace App\Filament\Resources\ViolationCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ViolationCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('violations.categories.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('types_count')
                    ->label(__('violations.categories.fields.types_count'))
                    ->counts('types')
                    ->badge()
                    ->color('info'),

                IconColumn::make('is_active')
                    ->label(__('violations.categories.fields.is_active'))
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->label(__('violations.categories.fields.sort_order'))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('general.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('violations.categories.fields.is_active')),
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
