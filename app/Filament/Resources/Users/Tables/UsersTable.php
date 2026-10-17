<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label(__('admin.avatar'))
                    ->circular()
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?background=2299dd&color=fff&name=User'),

                TextColumn::make('name')
                    ->label(__('admin.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->label(__('admin.email'))
                    ->searchable()
                    ->copyable()
                    ->copyMessage(__('admin.copy_email_message'))
                    ->sortable(),

                TextColumn::make('phone')
                    ->label(__('admin.phone'))
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('employee.branch.name')
                    ->label(__('admin.branch'))
                    ->badge()
                    ->color('info')
                    ->placeholder(__('admin.all_branches'))
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label(__('admin.roles_and_permissions'))
                    ->badge()
                    ->color('primary')
                    ->separator(', ')
                    ->placeholder(__('admin.no_roles')),

                IconColumn::make('status')
                    ->label(__('admin.status'))
                    ->getStateUsing(fn ($record) => $record->status === 'active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()->label(__('admin.edit')),
                DeleteAction::make()->label(__('admin.delete')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
