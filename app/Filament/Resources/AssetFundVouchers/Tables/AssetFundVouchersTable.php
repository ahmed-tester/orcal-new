<?php

namespace App\Filament\Resources\AssetFundVouchers\Tables;

use App\Enums\AssetFundVoucherType;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssetFundVouchersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('voucher_number')
                    ->label(__('asset_funds.asset_fund_vouchers.fields.voucher_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('fund.fund_name')
                    ->label(__('asset_funds.asset_fund_vouchers.fields.fund'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label(__('asset_funds.asset_fund_vouchers.fields.type'))
                    ->badge(),

                TextColumn::make('voucher_date')
                    ->label(__('asset_funds.asset_fund_vouchers.fields.voucher_date'))
                    ->date()
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label(__('asset_funds.asset_fund_vouchers.fields.total_amount'))
                    ->money('SAR')
                    ->sortable()
                    ->color(fn ($record) => $record->type?->value === AssetFundVoucherType::DEPOSIT->value ? 'success' : 'danger')
                    ->weight('bold'),

                TextColumn::make('party_display')
                    ->label(__('asset_funds.asset_fund_vouchers.fields.party_name'))
                    ->getStateUsing(fn ($record) => $record->party_name ?: ($record->company?->trade_name ?? $record->company?->name ?? '—')),

                TextColumn::make('notes')
                    ->label(__('asset_funds.asset_fund_vouchers.fields.notes'))
                    ->limit(50)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('asset_funds.asset_fund_vouchers.fields.type'))
                    ->options(AssetFundVoucherType::class),

                SelectFilter::make('asset_fund_id')
                    ->label(__('asset_funds.asset_fund_vouchers.fields.fund'))
                    ->relationship('fund', 'fund_name'),
            ])
            ->defaultSort('voucher_date', 'desc')
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

