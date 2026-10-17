<?php

namespace App\Filament\Resources\Tasks\Tables;

use App\Enums\GeneralStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('task_code')
                    ->label(__('tasks.fields.task_code'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('name')
                    ->label(__('tasks.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('default_weight')
                    ->label(__('tasks.fields.default_weight'))
                    ->suffix('%')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->color('primary')
                    ->weight('medium'),

                TextColumn::make('status')
                    ->label(__('tasks.fields.status'))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('tasks.fields.status'))
                    ->options(GeneralStatus::class),
            ])
            ->recordActions([
                EditAction::make()->label(__('employees.actions.edit')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('employees.actions.delete_selected')),
                ]),
            ]);
    }
}
