<?php

namespace App\Filament\Resources\Installments\Tables;

use App\Enums\InstallmentCategory;
use App\Enums\InstallmentStatus;
use App\Filament\Components\OrgSelect;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InstallmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->heading(fn () => __('finance.installments.table_heading', [
                'count' => \App\Filament\Resources\Installments\InstallmentResource::getEloquentQuery()->count(),
            ]))
            ->columns([
                TextColumn::make('installment_number')
                    ->label(__('finance.installments.fields.installment_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('transaction_type')
                    ->label(__('finance.installments.fields.transaction_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'advance' ? __('finance.installments.badges.advance') : __('finance.installments.badges.installment'))
                    ->color(fn ($state) => $state === 'advance' ? 'success' : 'info')
                    ->sortable(),

                TextColumn::make('beneficiary_type')
                    ->label(__('finance.installments.fields.beneficiary_type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'external' ? __('finance.installments.badges.external') : __('finance.installments.badges.internal'))
                    ->color(fn ($state) => $state === 'external' ? 'warning' : 'gray')
                    ->sortable(),

                TextColumn::make('installment_date')
                    ->label(__('finance.installments.fields.installment_date'))
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('beneficiary_name')
                    ->label(__('finance.installments.fields.beneficiary'))
                    ->searchable(query: function ($query, string $search) {
                        $query->where('external_name', 'like', "%{$search}%")
                            ->orWhere('external_national_id', 'like', "%{$search}%")
                            ->orWhere('external_phone', 'like', "%{$search}%")
                            ->orWhereHas('employee.user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                    })
                    ->weight('bold')
                    ->description(function ($record) {
                        if ($record->beneficiary_type === 'external') {
                            $idText = $record->external_national_id ? "{$record->external_national_id}" : '';
                            $phoneText = $record->external_phone ? "{$record->external_phone}" : '';
                            return trim("{$idText} | {$phoneText}", " |");
                        }
                        return $record->employee?->employee_number ? $record->employee->employee_number : null;
                    }),

                OrgSelect::tableColumnCompany('company.trade_name', __('finance.installments.fields.company')),

                TextColumn::make('installmentType.name')
                    ->label(__('finance.installments.fields.installment_type'))
                    ->placeholder(__('finance.payment_collection_items.categories.other'))
                    ->searchable(),

                TextColumn::make('guarantor.name')
                    ->label(__('finance.installments.fields.guarantor'))
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('total_amount')
                    ->label(__('finance.installments.fields.total_amount'))
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('paid_amount')
                    ->label(__('finance.installments.fields.paid_amount'))
                    ->money('SAR')
                    ->sortable()
                    ->color('success'),

                TextColumn::make('remaining_amount')
                    ->label(__('finance.installments.fields.remaining_amount'))
                    ->money('SAR')
                    ->sortable()
                    ->weight('bold')
                    ->color('warning'),

                TextColumn::make('monthly_amount')
                    ->label(__('finance.installments.fields.monthly_amount'))
                    ->money('SAR')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label(__('finance.installments.fields.status'))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('transaction_type')
                    ->label(__('finance.installments.fields.transaction_type'))
                    ->options([
                        'installment' => __('finance.installments.toggle_options.installment'),
                        'advance'     => __('finance.installments.toggle_options.advance'),
                    ]),

                SelectFilter::make('beneficiary_type')
                    ->label(__('finance.installments.fields.beneficiary_type'))
                    ->options([
                        'internal' => __('finance.installments.sources.internal'),
                        'external' => __('finance.installments.sources.external'),
                    ]),

                OrgSelect::tableFilterCompany('company_id', __('finance.installments.fields.company')),

                SelectFilter::make('status')
                    ->label(__('finance.installments.fields.status'))
                    ->options(InstallmentStatus::class),

                SelectFilter::make('category')
                    ->label(__('finance.installment_types.fields.category'))
                    ->options(InstallmentCategory::class)
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('installmentType', fn ($q) => $q->where('category', $data['value']));
                        }
                    }),
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
