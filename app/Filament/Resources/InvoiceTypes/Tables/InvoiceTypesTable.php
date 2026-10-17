<?php

namespace App\Filament\Resources\InvoiceTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoiceTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('invoices.fields.invoice_type'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('code')
                    ->label(__('invoices.fields.code'))
                    ->badge()
                    ->color('gray')
                    ->placeholder('—'),

                TextColumn::make('company.trade_name')
                    ->label(__('invoices.fields.company'))
                    ->placeholder(__('invoices.placeholders.general_to_all'))
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('invoices_count')
                    ->counts('invoices')
                    ->label(__('invoices.fields.invoices_count'))
                    ->badge()
                    ->color('info'),

                TextColumn::make('status')
                    ->label(__('invoices.fields.status'))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label(__('invoices.fields.company'))
                    ->relationship('company', 'trade_name'),

                SelectFilter::make('status')
                    ->label(__('invoices.fields.status'))
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
