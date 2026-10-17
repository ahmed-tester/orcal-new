<?php

namespace App\Filament\Resources\Debts\Tables;

use App\Filament\Components\OrgSelect;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DebtsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading(fn () => __('debts.table_heading', [
                'count' => \App\Filament\Resources\Debts\DebtResource::getEloquentQuery()->count(),
            ]))
            ->columns([
                TextColumn::make('debt_number')
                    ->label(__('debts.fields.debt_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('debtor_type')
                    ->label(__('debts.fields.debtor_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'external' ? __('debts.badges.external') : __('debts.badges.internal'))
                    ->color(fn ($state) => $state === 'external' ? 'warning' : 'gray')
                    ->sortable(),

                TextColumn::make('beneficiary_name')
                    ->label(__('debts.fields.debtor'))
                    ->searchable(query: function ($query, string $search) {
                        $query->where('external_name', 'like', "%{$search}%")
                            ->orWhere('external_national_id', 'like', "%{$search}%")
                            ->orWhere('external_phone', 'like', "%{$search}%")
                            ->orWhereHas('employee.user', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('company', fn ($q) => $q->where('trade_name', 'like', "%{$search}%"));
                    })
                    ->weight('bold')
                    ->description(function ($record) {
                        if ($record->debtor_type === 'external') {
                            $idText = $record->external_national_id ? "{$record->external_national_id}" : '';
                            $phoneText = $record->external_phone ? "{$record->external_phone}" : '';
                            return trim("{$idText} | {$phoneText}", " |");
                        }
                        return $record->employee?->employee_number ?? null;
                    }),

                OrgSelect::tableColumnCompany('company.trade_name', __('debts.fields.company')),

                TextColumn::make('guarantor.name')
                    ->label(__('debts.fields.guarantor'))
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('debt_reason')
                    ->label(__('debts.fields.debt_reason'))
                    ->limit(25)
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label(__('debts.fields.total_amount'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('paid_amount')
                    ->label(__('debts.fields.paid_amount'))
                    ->money('SAR')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('remaining_amount')
                    ->label(__('debts.fields.remaining_amount'))
                    ->money('SAR')
                    ->sortable()
                    ->weight('bold')
                    ->color('danger'),

                TextColumn::make('debt_date')
                    ->label(__('debts.fields.debt_date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label(__('debts.fields.due_date'))
                    ->date('Y-m-d')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label(__('debts.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'paid'    => __('debts.badges.paid'),
                        'partial' => __('debts.badges.partial'),
                        'overdue' => __('debts.badges.overdue'),
                        default   => __('debts.badges.pending'),
                    })
                    ->color(fn ($state) => match ($state) {
                        'paid'    => 'success',
                        'partial' => 'info',
                        'overdue' => 'danger',
                        default   => 'warning',
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('debtor_type')
                    ->label(__('debts.fields.debtor_type'))
                    ->options([
                        'internal' => __('debts.toggle_options.internal'),
                        'external' => __('debts.toggle_options.external'),
                    ]),

                SelectFilter::make('status')
                    ->label(__('debts.fields.status'))
                    ->options([
                        'pending' => __('debts.statuses.pending'),
                        'partial' => __('debts.statuses.partial'),
                        'paid'    => __('debts.statuses.paid'),
                        'overdue' => __('debts.statuses.overdue'),
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    \Filament\Actions\Action::make('print_voucher')
                        ->label('طباعة سند الدين')
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->url(fn ($record) => route('exports.debt-voucher', $record))
                        ->openUrlInNewTab(),
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
