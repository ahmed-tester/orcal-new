<?php

namespace App\Filament\Resources\Services\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('services.fields.name'))
                    ->description(fn (\App\Models\Service $record) => $record->beneficiary_name ? '👤 المستفيد: ' . $record->beneficiary_name : null)
                    ->searchable(['name', 'beneficiary_name'])
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('company.trade_name')
                    ->label(__('services.fields.company'))
                    ->badge()
                    ->color(fn ($record) => $record->company_id ? 'gray' : 'success')
                    ->placeholder('🌐 عامة لكافة المنشآت')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price')
                    ->label(__('services.fields.price'))
                    ->money('SAR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('status')
                    ->label(__('services.fields.status'))
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('scope')
                    ->label('نطاق الخدمة')
                    ->options([
                        'general' => 'خدمات عامة لكافة المنشآت',
                        'company' => 'خدمات مخصصة لمنشأة',
                    ])
                    ->query(function ($query, array $data) {
                        if (($data['value'] ?? null) === 'general') {
                            $query->whereNull('company_id');
                        } elseif (($data['value'] ?? null) === 'company') {
                            $query->whereNotNull('company_id');
                        }
                    }),

                SelectFilter::make('company_id')
                    ->label(__('services.fields.company'))
                    ->relationship('company', 'trade_name'),

                SelectFilter::make('status')
                    ->label(__('services.fields.status'))
                    ->options(\App\Enums\GeneralStatus::class),
            ])
            ->recordActions([
                EditAction::make()->label(__('employees.actions.edit')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('employees.actions.delete_selected')),
                ]),
            ]);
    }
}
