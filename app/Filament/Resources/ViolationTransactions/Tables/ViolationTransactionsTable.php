<?php

namespace App\Filament\Resources\ViolationTransactions\Tables;

use App\Enums\ViolationTransactionType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ViolationTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('voucher_number')
                    ->label(__('violations.transactions.fields.voucher_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('transaction_date')
                    ->label(__('violations.transactions.fields.transaction_date'))
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('Y/m/d') . ' م<br><span style="font-size:0.75rem; color:#64748b;">' . \App\Services\HijriDateService::toHijriNumeric($state) . ' هـ</span>' : '—')
                    ->html()
                    ->sortable(),

                TextColumn::make('company.trade_name')
                    ->label(__('violations.transactions.fields.company'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('violation.violation_number')
                    ->label(__('violations.transactions.fields.violation'))
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('transaction_type')
                    ->label(__('violations.transactions.fields.transaction_type'))
                    ->badge(),

                TextColumn::make('amount')
                    ->label(__('violations.transactions.fields.amount'))
                    ->numeric(2)
                    ->suffix(' SAR')
                    ->color(fn ($record) => $record->transaction_type === ViolationTransactionType::INCOMING ? 'success' : 'danger')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('balance_after')
                    ->label(__('violations.transactions.fields.balance_after'))
                    ->numeric(2)
                    ->suffix(' SAR')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('executor.name')
                    ->label(__('violations.transactions.fields.executed_by'))
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label(__('general.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label(__('violations.transactions.fields.company'))
                    ->relationship('company', 'trade_name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('transaction_type')
                    ->label(__('violations.transactions.fields.transaction_type'))
                    ->options(ViolationTransactionType::class),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('print_voucher')
                    ->label('طباعة السند PDF')
                    ->icon('heroicon-o-printer')
                    ->color('warning')
                    ->url(fn ($record) => route('exports.violation-voucher', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Deletion prevented
                ]),
            ]);
    }
}
