<?php

namespace App\Filament\Resources\EmployeeRewards\Tables;

use App\Enums\RewardStatus;
use App\Filament\Components\OrgSelect;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmployeeRewardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading(fn () => __('finance.employee_rewards.table_heading', [
                'count' => \App\Filament\Resources\EmployeeRewards\EmployeeRewardResource::getEloquentQuery()->count(),
            ]))
            ->columns([
                TextColumn::make('reward_number')
                    ->label(__('finance.employee_rewards.fields.reward_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('reward_date')
                    ->label(__('finance.employee_rewards.fields.reward_date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('employee.user.name')
                    ->label(__('finance.employee_rewards.fields.employee'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->employee?->employee_number ? '🆔 ' . $record->employee->employee_number : null),

                OrgSelect::tableColumnCompany('company.trade_name', __('finance.employee_rewards.fields.company')),

                TextColumn::make('hours_count')
                    ->label(__('finance.employee_rewards.fields.hours_count'))
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                TextColumn::make('minutes_count')
                    ->label(__('finance.employee_rewards.fields.minutes_count'))
                    ->badge()
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('net_amount')
                    ->label(__('finance.employee_rewards.fields.net_amount'))
                    ->money('SAR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('reason')
                    ->label(__('finance.employee_rewards.fields.reason'))
                    ->searchable()
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label(__('finance.employee_rewards.fields.status'))
                    ->badge(),
            ])
            ->filters([
                OrgSelect::tableFilterCompany('company_id', __('finance.employee_rewards.fields.company')),

                SelectFilter::make('status')
                    ->label(__('finance.employee_rewards.fields.status'))
                    ->options(RewardStatus::class),
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
