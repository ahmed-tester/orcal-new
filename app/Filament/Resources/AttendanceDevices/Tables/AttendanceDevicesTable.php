<?php

namespace App\Filament\Resources\AttendanceDevices\Tables;

use App\Filament\Components\OrgSelect;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AttendanceDevicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('attendance_devices.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('code')
                    ->label(__('attendance_devices.fields.code'))
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->copyable(),

                OrgSelect::tableColumnCompany(),

                TextColumn::make('branch.name')
                    ->label(__('attendance_devices.fields.branch'))
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                TextColumn::make('model')
                    ->label(__('attendance_devices.fields.model'))
                    ->placeholder(__('attendance_devices.placeholders.unspecified'))
                    ->toggleable(),

                TextColumn::make('last_seen_at')
                    ->label(__('attendance_devices.fields.last_seen_at'))
                    ->since()
                    ->badge()
                    ->color(fn ($record) => $record->last_seen_at && $record->last_seen_at->gt(now()->subMinutes(10)) ? 'success' : 'gray'),

                TextColumn::make('status')
                    ->label(__('attendance_devices.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active' => __('attendance_devices.statuses.active'),
                        'inactive' => __('attendance_devices.statuses.inactive'),
                        'maintenance' => __('attendance_devices.statuses.maintenance'),
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'maintenance' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('attendance_devices.fields.status'))
                    ->options([
                        'active' => __('attendance_devices.statuses.active'),
                        'inactive' => __('attendance_devices.statuses.inactive'),
                        'maintenance' => __('attendance_devices.statuses.maintenance'),
                    ]),
            ])
            ->recordActions([
                EditAction::make()->label(__('admin.edit')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('admin.delete')),
                ]),
            ]);
    }
}
