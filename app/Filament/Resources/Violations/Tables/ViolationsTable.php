<?php

namespace App\Filament\Resources\Violations\Tables;

use App\Enums\ViolationPaymentStatus;
use App\Enums\ViolationStage;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ViolationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('violation_number')
                    ->label(__('violations.violations.fields.violation_number'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('company.trade_name')
                    ->label(__('violations.violations.fields.company'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('category.name')
                    ->label(__('violations.violations.fields.category'))
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('type.name')
                    ->label(__('violations.violations.fields.type'))
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('external_violation_number')
                    ->label(__('violations.violations.fields.external_violation_number'))
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('violation_date')
                    ->label(__('violations.violations.fields.violation_date'))
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('Y/m/d') . ' م<br><span style="font-size:0.75rem; color:#64748b;">' . \App\Services\HijriDateService::toHijriNumeric($state) . ' هـ</span>' : '—')
                    ->html()
                    ->sortable(),

                TextColumn::make('issuing_authority')
                    ->label(__('violations.violations.fields.issuing_authority'))
                    ->searchable(),

                TextColumn::make('violation_amount')
                    ->label(__('violations.violations.fields.violation_amount'))
                    ->numeric(2)
                    ->suffix(' SAR')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('paid_amount')
                    ->label(__('violations.violations.fields.paid_amount'))
                    ->numeric(2)
                    ->suffix(' SAR')
                    ->color('success')
                    ->sortable(),

                TextColumn::make('remaining_amount')
                    ->label(__('violations.violations.fields.remaining_amount'))
                    ->numeric(2)
                    ->suffix(' SAR')
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'success')
                    ->sortable(),

                TextColumn::make('stage')
                    ->label(__('violations.violations.fields.stage'))
                    ->badge(),

                TextColumn::make('payment_status')
                    ->label(__('violations.violations.fields.payment_status'))
                    ->badge(),

                TextColumn::make('created_at')
                    ->label(__('general.created_at'))
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label(__('violations.violations.fields.company'))
                    ->relationship('company', 'trade_name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('violation_category_id')
                    ->label(__('violations.violations.fields.category'))
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('stage')
                    ->label(__('violations.violations.fields.stage'))
                    ->options(ViolationStage::class),

                SelectFilter::make('payment_status')
                    ->label(__('violations.violations.fields.payment_status'))
                    ->options(ViolationPaymentStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
