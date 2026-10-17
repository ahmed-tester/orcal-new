<?php

namespace App\Filament\Resources\TaskAssignments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TaskAssignmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_number')
                    ->label(__('tasks.fields.transaction_number'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('assignment_date')
                    ->label(__('tasks.fields.assignment_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('employee.user.name')
                    ->label(__('tasks.fields.employee'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('items_count')
                    ->counts('items')
                    ->label(__('tasks.fields.total_tasks'))
                    ->badge()
                    ->color('primary')
                    ->alignCenter(),

                TextColumn::make('total_weight')
                    ->label(__('tasks.fields.total_weight'))
                    ->suffix('%')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('total_score')
                    ->label(__('tasks.fields.total_score'))
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),

                TextColumn::make('overall_percentage')
                    ->label(__('tasks.fields.overall_percentage'))
                    ->suffix('%')
                    ->numeric(decimalPlaces: 2)
                    ->badge()
                    ->color(fn ($state) => $state >= 80 ? 'success' : ($state >= 50 ? 'warning' : 'danger'))
                    ->sortable(),

                IconColumn::make('is_completed')
                    ->label(__('tasks.fields.is_completed'))
                    ->boolean()
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('employee_id')
                    ->label(__('tasks.fields.employee'))
                    ->relationship('employee', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name),

                Filter::make('is_completed')
                    ->label(__('tasks.fields.is_completed'))
                    ->query(fn (Builder $query): Builder => $query->where('is_completed', true)),
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
