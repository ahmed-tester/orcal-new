<?php

namespace App\Filament\Resources\DebtCollections\Tables;

use App\Enums\ApprovalStatus;
use App\Filament\Components\ApprovalActions;
use App\Filament\Resources\DebtCollections\DebtCollectionResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DebtCollectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading(fn () => __('debts.collections.table_heading', [
                'count' => DebtCollectionResource::getEloquentQuery()->count(),
            ]))
            ->columns([
                TextColumn::make('voucher_number')
                    ->label(__('debts.collections.fields.voucher_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('status')
                    ->label(__('debts.collections.fields.status'))
                    ->badge()
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('metadata->status', $direction)),

                TextColumn::make('collection_date')
                    ->label(__('debts.collections.fields.collection_date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('debt.debt_number')
                    ->label(__('debts.collections.fields.debt'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('debt.beneficiary_name')
                    ->label(__('debts.fields.debtor'))
                    ->weight('bold')
                    ->searchable(),

                TextColumn::make('amount')
                    ->label(__('debts.collections.fields.amount'))
                    ->money('SAR')
                    ->sortable()
                    ->color('success')
                    ->weight('bold'),

                TextColumn::make('payment_method')
                    ->label(__('debts.collections.fields.payment_method'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'bank_transfer' => __('debts.collections.payment_methods.bank_transfer'),
                        'cheque' => __('debts.collections.payment_methods.cheque'),
                        default => __('debts.collections.payment_methods.cash'),
                    })
                    ->color(fn ($state) => match ($state) {
                        'bank_transfer' => 'info',
                        'cheque' => 'warning',
                        default => 'success',
                    }),

                TextColumn::make('treasuryTransaction.voucher_number')
                    ->label(__('debts.collections.fields.treasury_transaction'))
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('executor.user.name')
                    ->label(__('debts.collections.fields.executed_by'))
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('debts.collections.fields.status'))
                    ->options(ApprovalStatus::class)
                    ->query(function ($query, array $data) {
                        if (! empty($data['value'])) {
                            $query->where('metadata->status', $data['value']);
                        }
                    }),

                SelectFilter::make('payment_method')
                    ->label(__('debts.collections.fields.payment_method'))
                    ->options([
                        'cash' => __('debts.collections.payment_methods.cash'),
                        'bank_transfer' => __('debts.collections.payment_methods.bank_transfer'),
                        'cheque' => __('debts.collections.payment_methods.cheque'),
                    ]),
            ])
            ->recordActions([
                ...ApprovalActions::make('approve_debt_collection', translationKey: 'debts.collections.actions'),

                Action::make('print_voucher')
                    ->label(__('debts.collections.actions.print_voucher'))
                    ->icon('heroicon-o-printer')
                    ->color('warning')
                    ->url(fn ($record) => route('system.print', ['type' => 'debt-collection', 'id' => $record->id]))
                    ->openUrlInNewTab(),

                ActionGroup::make([
                    Action::make('print_voucher_menu')
                        ->label(__('debts.collections.actions.print_voucher'))
                        ->icon('heroicon-o-printer')
                        ->url(fn ($record) => route('system.print', ['type' => 'debt-collection', 'id' => $record->id]))
                        ->openUrlInNewTab(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
