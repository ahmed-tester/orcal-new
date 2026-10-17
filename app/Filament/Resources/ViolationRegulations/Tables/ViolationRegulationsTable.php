<?php

namespace App\Filament\Resources\ViolationRegulations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ViolationRegulationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('violations.regulations.fields.title'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('content')
                    ->label(__('violations.regulations.fields.content'))
                    ->html()
                    ->limit(60)
                    ->tooltip(fn ($record) => strip_tags($record->content)),

                IconColumn::make('is_active')
                    ->label(__('violations.regulations.fields.is_active'))
                    ->boolean(),

                TextColumn::make('sort_order')
                    ->label(__('violations.regulations.fields.sort_order'))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('general.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('violations.regulations.fields.is_active')),
            ])
            ->recordActions([
                ViewAction::make(),
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
