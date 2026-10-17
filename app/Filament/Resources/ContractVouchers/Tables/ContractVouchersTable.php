<?php

namespace App\Filament\Resources\ContractVouchers\Tables;

use App\Models\ContractVoucher;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContractVouchersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('voucher_type')
                    ->label(__('contract_vouchers.fields.voucher_type'))
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'payment' => __('contract_vouchers.types.payment'),
                        'request' => __('contract_vouchers.types.request'),
                        default   => __('contract_vouchers.types.receipt'),
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'payment' => 'danger',
                        'request' => 'warning',
                        default   => 'success',
                    })
                    ->icon(fn ($state) => match ($state) {
                        'payment' => 'heroicon-m-arrow-up-tray',
                        'request' => 'heroicon-m-clipboard-document-check',
                        default   => 'heroicon-m-arrow-down-tray',
                    })
                    ->sortable(),

                TextColumn::make('voucher_number')
                    ->label(__('contract_vouchers.fields.voucher_number'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-m-document-text'),

                TextColumn::make('voucher_date')
                    ->label(__('contract_vouchers.fields.voucher_date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('amount')
                    ->label(__('contract_vouchers.fields.amount'))
                    ->numeric(decimalPlaces: 2)
                    ->suffix(' ' . __('offers.currency_symbol'))
                    ->weight('bold')
                    ->color(fn ($record) => $record->voucher_type === 'payment' ? 'danger' : 'success')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label(__('contract_vouchers.fields.payment_method'))
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'check'         => __('contract_vouchers.payment_methods.check'),
                        'bank_transfer' => __('contract_vouchers.payment_methods.bank_transfer'),
                        'cash'          => __('contract_vouchers.payment_methods.cash'),
                        default         => '—',
                    })
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),

                TextColumn::make('party_display')
                    ->label(__('contract_vouchers.fields.second_party_name'))
                    ->state(fn (ContractVoucher $record) => $record->customer?->name ?: ($record->second_party_name ?: ($record->first_party_name ?: '—')))
                    ->searchable(query: fn ($query, $search) => $query->where('second_party_name', 'like', "%{$search}%")->orWhere('first_party_name', 'like', "%{$search}%"))
                    ->weight('medium'),

                TextColumn::make('contract_number')
                    ->label(__('contract_vouchers.fields.contract_number'))
                    ->placeholder('—')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                TextColumn::make('status')
                    ->label(__('contract_vouchers.fields.status'))
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'approved'  => __('contract_vouchers.statuses.approved'),
                        'cancelled' => __('contract_vouchers.statuses.cancelled'),
                        default     => __('contract_vouchers.statuses.draft'),
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'approved'  => 'success',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('voucher_type')
                    ->label(__('contract_vouchers.fields.voucher_type'))
                    ->options([
                        'receipt' => __('contract_vouchers.types.receipt'),
                        'payment' => __('contract_vouchers.types.payment'),
                        'request' => __('contract_vouchers.types.request'),
                    ]),

                SelectFilter::make('payment_method')
                    ->label(__('contract_vouchers.fields.payment_method'))
                    ->options([
                        'cash'          => __('contract_vouchers.payment_methods.cash'),
                        'check'         => __('contract_vouchers.payment_methods.check'),
                        'bank_transfer' => __('contract_vouchers.payment_methods.bank_transfer'),
                    ]),

                SelectFilter::make('status')
                    ->label(__('contract_vouchers.fields.status'))
                    ->options([
                        'draft'     => __('contract_vouchers.statuses.draft'),
                        'approved'  => __('contract_vouchers.statuses.approved'),
                        'cancelled' => __('contract_vouchers.statuses.cancelled'),
                    ]),

                SelectFilter::make('company_id')
                    ->label(__('contract_vouchers.fields.company'))
                    ->relationship('company', 'trade_name'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(__('contract_vouchers.actions.approve'))
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (ContractVoucher $record) => $record->status !== 'approved' && in_array($record->voucher_type, ['receipt', 'payment']))
                    ->requiresConfirmation()
                    ->action(function (ContractVoucher $record) {
                        $record->approveAndPostToTreasury();
                        Notification::make()
                            ->title(__('contract_vouchers.messages.approved_success'))
                            ->success()
                            ->send();
                    }),

                Action::make('print')
                    ->label(__('contract_vouchers.actions.print'))
                    ->icon('heroicon-m-printer')
                    ->color('info')
                    ->iconButton()
                    ->tooltip(__('contract_vouchers.actions.print'))
                    ->url(fn ($record) => route('contract_vouchers.print', $record))
                    ->openUrlInNewTab(),

                EditAction::make()
                    ->iconButton()
                    ->tooltip(__('contract_vouchers.actions.edit')),

                DeleteAction::make()
                    ->iconButton()
                    ->tooltip(__('contract_vouchers.actions.delete')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('voucher_date', 'desc');
    }
}
