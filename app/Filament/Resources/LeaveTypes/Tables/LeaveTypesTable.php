<?php

namespace App\Filament\Resources\LeaveTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeaveTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('organization.leave_types.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                // TextColumn::make('company.trade_name')
                //     ->label(__('organization.leave_types.fields.company'))
                //     ->searchable()
                //     ->sortable()
                //     ->badge()
                //     ->color('primary'),

                TextColumn::make('annual_balance')
                    ->label(__('organization.leave_types.fields.annual_balance'))
                    ->suffix(' ' . __('leaves.units.day'))
                    ->sortable(),

                IconColumn::make('is_paid')
                    ->label(__('organization.leave_types.fields.is_paid'))
                    ->boolean(),

                IconColumn::make('requires_approval')
                    ->label(__('organization.leave_types.fields.requires_approval'))
                    ->boolean(),

                TextColumn::make('status')
                    ->label(__('organization.leave_types.fields.status'))
                    ->badge(),
            ])
            ->filters([
                // SelectFilter::make('company_id')
                //     ->label(__('organization.leave_types.fields.company'))
                //     ->relationship('company', 'trade_name')
                //     ->searchable()
                //     ->preload(),

                SelectFilter::make('status')
                    ->label(__('organization.leave_types.fields.status'))
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
