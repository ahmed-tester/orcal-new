<?php

namespace App\Filament\Resources\Departments\Tables;

use App\Models\Department;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DepartmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('department_code')
                    ->label(__('organization.departments.fields.department_code'))
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('name')
                    ->label(__('organization.departments.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('parent.name')
                    ->label(__('organization.departments.fields.parent'))
                    ->placeholder('-')
                    ->badge()
                    ->color('info'),

                TextColumn::make('companies.trade_name')
                    ->label(__('organization.departments.fields.companies'))
                    ->formatStateUsing(fn ($state, Department $record) => $record->is_global ? __('organization.departments.fields.global_badge') : ($record->companies->isNotEmpty() ? $record->companies->pluck('trade_name')->join('، ') : ($record->company?->trade_name ?? '-')))
                    ->badge(fn (Department $record) => $record->is_global)
                    ->color(fn (Department $record) => $record->is_global ? 'success' : 'gray')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('status')
                    ->label(__('organization.departments.fields.status'))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label(__('organization.departments.fields.company'))
                    ->relationship('company', 'trade_name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label(__('organization.departments.fields.status'))
                    ->options(\App\Enums\GeneralStatus::class),
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
