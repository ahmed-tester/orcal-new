<?php

namespace App\Filament\Resources\PaymentCollectionItems\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentCollectionItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading(fn () => __('finance.payment_collection_items.table_heading', [
                'count' => \App\Filament\Resources\PaymentCollectionItems\PaymentCollectionItemResource::getEloquentQuery()->count(),
            ]))
            ->columns([
                TextColumn::make('transaction_number')
                    ->label(__('finance.payment_collection_items.fields.transaction_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('company.trade_name')
                    ->label(__('finance.payment_collection_items.fields.company'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->visible(fn () => auth()->user()?->hasMultipleCompanies() ?? false),

                TextColumn::make('main_item')
                    ->label(__('finance.payment_collection_items.fields.main_item'))
                    ->searchable()
                    ->sortable()
                    ->badge(),

                TextColumn::make('sub_item')
                    ->label(__('finance.payment_collection_items.fields.sub_item'))
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('service_price')
                    ->label(__('finance.payment_collection_items.fields.service_price'))
                    ->formatStateUsing(fn ($state) => $state > 0 ? number_format($state, 2) . ' ' . __('finance.payment_collection_items.currency') : __('finance.payment_collection_items.empty_price'))
                    ->sortable(),

                TextColumn::make('usage_count')
                    ->label(__('finance.payment_collection_items.fields.usage_count'))
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label(__('finance.payment_collection_items.fields.company'))
                    ->relationship('company', 'trade_name')
                    ->visible(fn () => auth()->user()?->hasMultipleCompanies() ?? false),

                SelectFilter::make('main_item')
                    ->label(__('finance.payment_collection_items.fields.main_item'))
                    ->options(\App\Enums\PaymentItemCategory::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
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
