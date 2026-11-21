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

                TextColumn::make('violation_link')
                    ->label(__('violations.transactions.fields.violation'))
                    ->state(function ($record) {
                        if ($record->link_type === 'unlinked' || (empty($record->violation_id) && empty($record->metadata['selected_violation_ids']))) {
                            return __('violations.transactions.fields.link_types.unlinked');
                        }
                        if ($record->link_type === 'multiple' || (! empty($record->metadata['selected_violation_ids']))) {
                            $count = count($record->metadata['selected_violation_ids'] ?? []);
                            return __('violations.transactions.fields.multiple_badge', ['count' => $count]);
                        }
                        return $record->violation?->violation_number ?? '—';
                    })
                    ->badge()
                    ->color(function ($record) {
                        if ($record->link_type === 'unlinked' || (empty($record->violation_id) && empty($record->metadata['selected_violation_ids']))) {
                            return 'success';
                        }
                        if ($record->link_type === 'multiple' || (! empty($record->metadata['selected_violation_ids']))) {
                            return 'info';
                        }
                        return 'primary';
                    })
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('violation', fn ($q) => $q->where('violation_number', 'like', "%{$search}%"));
                    }),

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
                // Handled by top Filter Bar
            ])
            ->recordActions([
                \Filament\Actions\Action::make('print_voucher')
                    ->label(__('violations.transactions.actions.print_voucher'))
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
            ])
            ->defaultSort('id', 'desc');
    }
}
