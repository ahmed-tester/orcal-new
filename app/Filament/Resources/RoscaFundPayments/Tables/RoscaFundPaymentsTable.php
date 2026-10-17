<?php

namespace App\Filament\Resources\RoscaFundPayments\Tables;

use App\Enums\FundPaymentStatus;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RoscaFundPaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member.full_name')
                    ->label(__('funds.rosca_fund_payments.fields.member'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('roscaFund.fund_name')
                    ->label(__('funds.rosca_fund_payments.fields.fund'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('period_month')
                    ->label(__('funds.rosca_fund_payments.fields.period_month'))
                    ->date('Y-m')
                    ->sortable(),

                TextColumn::make('installment_order')
                    ->label(__('funds.rosca_fund_payments.fields.installment_order'))
                    ->sortable(),

                TextColumn::make('scheduled_amount')
                    ->label(__('funds.rosca_fund_payments.fields.scheduled_amount'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('paid_amount')
                    ->label(__('funds.rosca_fund_payments.fields.paid_amount'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('payment_status')
                    ->label(__('funds.rosca_fund_payments.fields.payment_status'))
                    ->badge(),

                IconColumn::make('is_bond_closed')
                    ->label(__('funds.rosca_fund_payments.fields.is_bond_closed'))
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('rosca_fund_id')
                    ->label(__('funds.rosca_fund_payments.fields.fund'))
                    ->relationship('roscaFund', 'fund_name'),

                SelectFilter::make('payment_status')
                    ->label(__('funds.rosca_fund_payments.fields.payment_status'))
                    ->options(FundPaymentStatus::class),
            ])
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
