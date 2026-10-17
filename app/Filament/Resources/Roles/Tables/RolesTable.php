<?php

namespace App\Filament\Resources\Roles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('roles.role_name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label(__('roles.permissions_count'))
                    ->badge()
                    ->color('primary'),

                TextColumn::make('users_count')
                    ->counts('users')
                    ->label(__('roles.admins_count'))
                    ->badge()
                    ->color('success'),

                TextColumn::make('created_at')
                    ->label(__('roles.created_at'))
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()->label(__('roles.edit')),
                DeleteAction::make()->label(__('roles.delete')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
