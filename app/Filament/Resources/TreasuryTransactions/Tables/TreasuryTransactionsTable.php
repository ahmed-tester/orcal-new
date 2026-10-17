<?php

namespace App\Filament\Resources\TreasuryTransactions\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TreasuryTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_number')
                    ->label(__('finance.treasury_transactions.fields.transaction_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('voucher_number')
                    ->label(__('finance.treasury_transactions.fields.voucher_number'))
                    ->searchable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('transaction_date')
                    ->label(__('finance.treasury_transactions.fields.transaction_date'))
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('Y/m/d') . ' م<br><span style="font-size:0.75rem; color:#64748b;">' . \App\Services\HijriDateService::toHijriNumeric($state) . ' هـ</span>' : '—')
                    ->html()
                    ->sortable(),

                TextColumn::make('company.trade_name')
                    ->label(__('finance.treasury_transactions.fields.company'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->visible(fn () => auth()->user()?->hasMultipleCompanies() ?? false),

                TextColumn::make('transaction_type')
                    ->label(__('finance.treasury_transactions.fields.transaction_type'))
                    ->badge(),

                TextColumn::make('paymentCollectionItem.sub_item')
                    ->label(__('finance.treasury_transactions.fields.payment_collection_item'))
                    ->searchable()
                    ->description(fn ($record) => $record->paymentCollectionItem?->main_item instanceof \App\Enums\PaymentItemCategory ? $record->paymentCollectionItem->main_item->getLabel() : (string)($record->paymentCollectionItem?->main_item ?? ''))
                    ->wrap(),

                TextColumn::make('recipient_or_payer')
                    ->label(__('finance.treasury_transactions.fields.recipient_or_payer'))
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('amount')
                    ->label(__('finance.treasury_transactions.fields.amount'))
                    ->money('SAR')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('balance_after')
                    ->label(__('finance.treasury_transactions.fields.balance_after'))
                    ->money('SAR')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('executor.user.name')
                    ->label(__('finance.treasury_transactions.fields.executed_by'))
                    ->searchable()
                    ->default('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label(__('finance.treasury_transactions.fields.company'))
                    ->relationship('company', 'trade_name')
                    ->visible(fn () => auth()->user()?->hasMultipleCompanies() ?? false),

                SelectFilter::make('transaction_type')
                    ->label(__('finance.treasury_transactions.fields.transaction_type'))
                    ->options(\App\Enums\TreasuryTransactionType::class),
            ])
            ->recordActions([
                Action::make('print_voucher')
                    ->label(__('finance.treasury_transactions.actions.print_voucher'))
                    ->icon('heroicon-o-printer')
                    ->color('warning')
                    ->url(fn ($record) => route('treasury.voucher.print', $record))
                    ->openUrlInNewTab(),
                \Filament\Actions\ActionGroup::make([
                    EditAction::make(),
                    // DeleteAction::make(), // تم تعطيل حذف حركات الخزينة
                ])
                ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    // DeleteBulkAction::make(), // تم تعطيل حذف حركات الخزينة
                ]),
            ])
            ->defaultSort('id', 'desc');
    }
}
