<?php

namespace App\Filament\Resources\PerformanceEscalations\Tables;

use App\Enums\PerformanceEscalationStatus;
use App\Filament\Actions\PerformanceEscalationActions;
use App\Models\Company;
use App\Models\PerformanceEscalation;
use App\Services\CompanyDepartmentPerformanceService;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PerformanceEscalationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('opened_at', 'desc')
            ->columns([
                TextColumn::make('company.trade_name')
                    ->label(__('performance_escalations.fields.company'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('subject_type')
                    ->label(__('performance_escalations.fields.subject_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => __('performance_escalations.subject_types.'.$state))
                    ->sortable(),

                TextColumn::make('subject_name')
                    ->label(__('performance_escalations.fields.subject'))
                    ->state(fn (PerformanceEscalation $record): string => $record->subjectName() ?: __('performance_escalations.subject_missing'))
                    ->wrap(),

                TextColumn::make('status')
                    ->label(__('performance_escalations.fields.status'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('score_at_open')
                    ->label(__('performance_escalations.fields.score_at_open'))
                    ->numeric(decimalPlaces: 0)
                    ->badge()
                    ->color(fn (PerformanceEscalation $record): string => $record->score_at_open < $record->threshold ? 'danger' : 'success')
                    ->sortable(),

                TextColumn::make('threshold')
                    ->label(__('performance_escalations.fields.threshold'))
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('rule_key')
                    ->label(__('performance_escalations.fields.rule_key'))
                    ->formatStateUsing(fn (string $state): string => __('performance_escalations.rules.'.$state))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('rules_version')
                    ->label(__('performance_escalations.fields.rules_version'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('opened_at')
                    ->label(__('performance_escalations.fields.opened_at'))
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('closed_at')
                    ->label(__('performance_escalations.fields.closed_at'))
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('closure_reason')
                    ->label(__('performance_escalations.fields.closure_reason'))
                    ->badge()
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label(__('performance_escalations.fields.company'))
                    ->options(fn (): array => Company::query()
                        ->whereKey(CompanyDepartmentPerformanceService::getAuthorizedCompanyIds())
                        ->orderBy('trade_name')
                        ->pluck('trade_name', 'id')
                        ->all()),

                SelectFilter::make('subject_type')
                    ->label(__('performance_escalations.fields.subject_type'))
                    ->options(collect(array_keys(PerformanceEscalation::SUBJECT_TYPES))
                        ->mapWithKeys(fn (string $type): array => [$type => __('performance_escalations.subject_types.'.$type)])
                        ->all()),

                SelectFilter::make('status')
                    ->label(__('performance_escalations.fields.status'))
                    ->options(PerformanceEscalationStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                PerformanceEscalationActions::recordManagementAction(),
            ]);
    }
}
