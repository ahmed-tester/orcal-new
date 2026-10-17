<?php

namespace App\Filament\Resources\DailyJournals\Tables;

use App\Filament\Components\OrgSelect;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DailyJournalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading(fn () => __('finance.daily_journals.table_heading', [
                'count' => \App\Filament\Resources\DailyJournals\DailyJournalResource::getEloquentQuery()->count(),
            ]))
            ->columns([
                TextColumn::make('entry_number')
                    ->label(__('finance.daily_journals.fields.entry_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('entry_date')
                    ->label(__('finance.daily_journals.fields.entry_date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('is_detailed')
                    ->label(__('finance.daily_journals.fields.journal_type'))
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state
                        ? __('finance.daily_journals.types.detailed')
                        : __('finance.daily_journals.types.summary'))
                    ->color(fn (bool $state): string => $state ? 'info' : 'gray'),

                OrgSelect::tableColumnCompany('company.trade_name', __('finance.daily_journals.fields.company')),

                OrgSelect::tableColumnBranch('branch.name', __('finance.daily_journals.fields.branch')),

                TextColumn::make('purchases')
                    ->label(__('finance.daily_journals.fields.purchases'))
                    ->money('SAR')
                    ->sortable()
                    ->color('danger'),

                TextColumn::make('sales')
                    ->label(__('finance.daily_journals.fields.sales'))
                    ->money('SAR')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('net_amount')
                    ->label(__('finance.daily_journals.fields.net_amount'))
                    ->money('SAR')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state > 0  => 'success',
                        $state < 0  => 'danger',
                        default     => 'gray',
                    }),

                TextColumn::make('recorder.user.name')
                    ->label(__('finance.daily_journals.fields.recorded_by'))
                    ->searchable()
                    ->default('—')
                    ->toggleable(),
            ])
            ->filters([
                OrgSelect::tableFilterCompany('company_id', __('finance.daily_journals.fields.company')),
                OrgSelect::tableFilterBranch('branch_id', __('finance.daily_journals.fields.branch')),
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
