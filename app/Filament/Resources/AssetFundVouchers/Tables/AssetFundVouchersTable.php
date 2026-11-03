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

                TextColumn::make('contractor_name')
                    ->label(__('asset_funds.asset_fund_vouchers.fields.contractor_name'))
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('party_display')
                    ->label(__('asset_funds.statement.cols.company'))
                    ->getStateUsing(fn ($record) => $record->party_name ?: ($record->company?->trade_name ?? $record->company?->name ?? '—'))
                    ->searchable(query: function ($query, string $search) {
                        $query->where('party_name', 'like', "%{$search}%")
                              ->orWhereHas('company', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('trade_name', 'like', "%{$search}%"));
                    }),

                TextColumn::make('tax_invoice_number')
                    ->label(__('asset_funds.asset_fund_vouchers.fields.tax_invoice_number'))
                    ->searchable()
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('gross_amount')
                    ->label(__('asset_funds.asset_fund_vouchers.fields.gross_amount'))
                    ->money('SAR')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('tax_amount')
                    ->label(__('asset_funds.asset_fund_vouchers.fields.tax_amount'))
                    ->money('SAR')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label(__('asset_funds.asset_fund_vouchers.fields.total_amount'))
                    ->money('SAR')
                    ->sortable()
                    ->color(fn ($record) => $record->type?->value === AssetFundVoucherType::DEPOSIT->value ? 'success' : 'danger')
                    ->weight('bold'),

                TextColumn::make('notes')
                    ->label(__('asset_funds.asset_fund_vouchers.fields.notes'))
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true)
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
