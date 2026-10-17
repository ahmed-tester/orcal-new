<?php

namespace App\Filament\Resources\Settings\Tables;

use App\Models\Setting;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label(__('settings.fields.label'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Setting $record) => $record->key),

                TextColumn::make('group')
                    ->label(__('settings.fields.group'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('settings.groups.' . $state) ?: $state)
                    ->colors([
                        'primary' => 'general',
                        'warning' => 'alerts',
                        'success' => 'hr',
                        'info' => 'appearance',
                        'danger' => 'uploads',
                        'gray' => fn ($state) => !in_array($state, ['general', 'alerts', 'hr', 'appearance', 'uploads']),
                    ])
                    ->sortable(),

                TextColumn::make('input_type')
                    ->label(__('settings.fields.input_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('settings.input_types.' . $state) ?: $state)
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('value')
                    ->label(__('settings.fields.value'))
                    ->limit(50)
                    ->searchable(),

                ToggleColumn::make('is_active')
                    ->label(__('settings.fields.is_active'))
                    ->onColor('success')
                    ->offColor('danger')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label(__('settings.fields.updated_at'))
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->label(__('settings.fields.group'))
                    ->options(__('settings.groups')),

                SelectFilter::make('input_type')
                    ->label(__('settings.fields.input_type'))
                    ->options(__('settings.input_types')),

                TernaryFilter::make('is_active')
                    ->label(__('settings.fields.is_active')),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
