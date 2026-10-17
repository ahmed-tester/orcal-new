<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Enums\CustomerType;
use App\Services\PhoneService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('customers.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => ($record->phone ? '📱 ' . PhoneService::format($record->phone) : '') . ($record->email ? ' | ✉️ ' . $record->email : '')),

                TextColumn::make('customer_type')
                    ->label(__('customers.fields.customer_type'))
                    ->badge(),

                TextColumn::make('national_id')
                    ->label(__('customers.fields.national_id'))
                    ->searchable()
                    ->placeholder('—')
                    ->icon('heroicon-m-identification'),

                TextColumn::make('commercial_registration_number')
                    ->label(__('customers.fields.cr_number'))
                    ->searchable()
                    ->placeholder('—')
                    ->icon('heroicon-m-document-text'),

                TextColumn::make('tax_number')
                    ->label(__('customers.fields.tax_number'))
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('city')
                    ->label(__('customers.fields.city'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label(__('customers.fields.status'))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('customer_type')
                    ->label(__('customers.fields.customer_type'))
                    ->options(CustomerType::class),

                SelectFilter::make('status')
                    ->label(__('customers.fields.status'))
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
