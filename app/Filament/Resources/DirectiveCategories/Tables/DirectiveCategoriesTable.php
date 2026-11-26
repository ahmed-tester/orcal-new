<?php

namespace App\Filament\Resources\DirectiveCategories\Tables;

use App\Models\Directives\DirectiveCategory;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DirectiveCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50, 100])
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('sort_order')
                    ->label(__('directives.fields.sort_order'))
                    ->sortable()
                    ->width(60),

                TextColumn::make('name_ar')
                    ->label(__('directives.fields.name_ar'))
                    ->badge()
                    ->color(fn (DirectiveCategory $record): string => $record->color ?: 'gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name_en')
                    ->label(__('directives.fields.name_en'))
                    ->hidden()
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(__('directives.fields.is_active'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('directives_count')
                    ->label(__('directives.fields.directives_count'))
                    ->counts('directives')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('directives.fields.is_active'))
                    ->trueLabel(__('directives.filters.active_only'))
                    ->falseLabel(__('directives.filters.inactive_only')),
            ])
            ->recordAction('edit')
            ->actions([
                EditAction::make(),
            ]);
    }
}
