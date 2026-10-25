<?php

namespace App\Filament\Resources\RoscaFundMembers\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RoscaFundMembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('turn_order')
                    ->label(__('funds.rosca_fund_members.fields.turn_order'))
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('full_name')
                    ->label(__('funds.rosca_fund_members.fields.full_name'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->national_id ? "🆔 {$record->national_id}" : null),

                TextColumn::make('phone')
                    ->label(__('funds.rosca_fund_members.fields.phone'))
                    ->searchable(),

                TextColumn::make('roscaFund.fund_name')
                    ->label(__('funds.rosca_fund_members.fields.fund'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('monthly_installment')
                    ->label(__('funds.rosca_fund_members.fields.monthly_installment'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('total_final_amount')
                    ->label(__('funds.rosca_fund_members.fields.total_final_amount'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('paid_amount')
                    ->label(__('funds.rosca_fund_members.fields.paid_amount'))
                    ->money('SAR')
                    ->sortable(),

                IconColumn::make('has_received_payout')
                    ->label(__('funds.rosca_fund_members.fields.has_received_payout'))
                    ->boolean(),

                TextColumn::make('status')
                    ->label(__('funds.rosca_fund_members.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active' => __('funds.enums.general_status.active'),
                        'completed' => __('funds.enums.general_status.completed'),
                        'dropped' => __('funds.enums.general_status.dropped'),
                        default => $state,
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'info',
                        'dropped' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('rosca_fund_id')
                    ->label(__('funds.rosca_fund_members.fields.fund'))
                    ->relationship('roscaFund', 'fund_name'),
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
