<?php

namespace App\Filament\Resources\PosDevices\Tables;

use App\Services\PhoneService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PosDevicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.trade_name')
                    ->label(__('finance.pos_devices.fields.company'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->visible(fn () => auth()->user()?->hasMultipleCompanies() ?? false),

                TextColumn::make('device_number')
                    ->label(__('finance.pos_devices.fields.device_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->copyable(),

                TextColumn::make('card_number')
                    ->label(__('finance.pos_devices.fields.card_number'))
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->copyable(),

                TextColumn::make('bank_or_wallet_name')
                    ->label(__('finance.pos_devices.fields.bank_or_wallet_name'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('account_number')
                    ->label(__('finance.pos_devices.fields.account_number'))
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('accountant_name')
                    ->label(__('finance.pos_devices.fields.accountant_name'))
                    ->formatStateUsing(fn ($state, $record) => $record->accountant?->user?->name ?? $state)
                    ->searchable()
                    ->description(function ($record) {
                        $phone = $record->accountant?->user?->phone ?? $record->accountant_phone;
                        $email = $record->accountant?->user?->email ?? $record->accountant_email;
                        $parts = [];
                        if ($phone) {
                            $parts[] = '📱 ' . PhoneService::format($phone);
                        }
                        if ($email) {
                            $parts[] = '✉️ ' . $email;
                        }
                        return count($parts) ? implode(' | ', $parts) : null;
                    }),

                TextColumn::make('status')
                    ->label(__('finance.pos_devices.fields.status'))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label(__('finance.pos_devices.fields.company'))
                    ->relationship('company', 'trade_name')
                    ->visible(fn () => auth()->user()?->hasMultipleCompanies() ?? false),

                SelectFilter::make('status')
                    ->label(__('finance.pos_devices.fields.status'))
                    ->options(\App\Enums\PosDeviceStatus::class),
            ])
            ->recordActions([
                \Filament\Actions\ActionGroup::make([
                    EditAction::make()
                        ->requiresConfirmation()
                        ->modalHeading(__('finance.pos_devices.modals.edit_heading'))
                        ->modalDescription(__('finance.pos_devices.modals.edit_desc')),
                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading(__('finance.pos_devices.modals.delete_heading'))
                        ->modalDescription(__('finance.pos_devices.modals.delete_desc')),
                ])
                ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
