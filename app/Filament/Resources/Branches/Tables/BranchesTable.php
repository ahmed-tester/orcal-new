<?php

namespace App\Filament\Resources\Branches\Tables;

use App\Services\PhoneService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BranchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('branch_code')
                    ->label(__('organization.branches.fields.branch_code'))
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('name')
                    ->label(__('organization.branches.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->phone ? '📱 ' . PhoneService::format($record->phone) : null),

                TextColumn::make('company.trade_name')
                    ->label(__('organization.branches.fields.company'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label(__('organization.branches.fields.email'))
                    ->searchable(),

                TextColumn::make('status')
                    ->label(__('organization.branches.fields.status'))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label(__('organization.branches.fields.company'))
                    ->relationship('company', 'trade_name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label(__('organization.branches.fields.status'))
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
