<?php

namespace App\Filament\Resources\PerformanceEscalations\Schemas;

use App\Models\PerformanceEscalation;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PerformanceEscalationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('performance_escalations.sections.case'))
                    ->columns(['default' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('company.trade_name')
                            ->label(__('performance_escalations.fields.company')),

                        TextEntry::make('subject_type')
                            ->label(__('performance_escalations.fields.subject_type'))
                            ->formatStateUsing(fn (string $state): string => __('performance_escalations.subject_types.'.$state)),

                        TextEntry::make('subject_name')
                            ->label(__('performance_escalations.fields.subject'))
                            ->state(fn (PerformanceEscalation $record): string => $record->subjectName() ?: __('performance_escalations.subject_missing')),

                        TextEntry::make('status')
                            ->label(__('performance_escalations.fields.status'))
                            ->badge(),

                        TextEntry::make('score_at_open')
                            ->label(__('performance_escalations.fields.score_at_open'))
                            ->badge()
                            ->color(fn (PerformanceEscalation $record): string => $record->score_at_open < $record->threshold ? 'danger' : 'success'),

                        TextEntry::make('threshold')
                            ->label(__('performance_escalations.fields.threshold')),

                        TextEntry::make('rule_key')
                            ->label(__('performance_escalations.fields.rule_key'))
                            ->formatStateUsing(fn (string $state): string => __('performance_escalations.rules.'.$state)),

                        TextEntry::make('rules_version')
                            ->label(__('performance_escalations.fields.rules_version')),

                        TextEntry::make('opened_at')
                            ->label(__('performance_escalations.fields.opened_at'))
                            ->dateTime('Y-m-d H:i'),

                        TextEntry::make('management_action_by_user_id')
                            ->label(__('performance_escalations.fields.management_action_by_user_id'))
                            ->state(fn (PerformanceEscalation $record): string => $record->managementActionUser?->name ?? '—'),

                        TextEntry::make('management_action_at')
                            ->label(__('performance_escalations.fields.management_action_at'))
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('—'),

                        TextEntry::make('management_action')
                            ->label(__('performance_escalations.fields.management_action'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),

                Section::make(__('performance_escalations.sections.closure'))
                    ->columns(['default' => 1, 'md' => 2, 'lg' => 3])
                    ->columnSpanFull()
                    ->visible(fn (PerformanceEscalation $record): bool => ! $record->isOpen())
                    ->schema([
                        TextEntry::make('closure_reason')
                            ->label(__('performance_escalations.fields.closure_reason'))
                            ->badge()
                            ->placeholder('—'),

                        TextEntry::make('close_score')
                            ->label(__('performance_escalations.fields.close_score'))
                            ->placeholder('—'),

                        TextEntry::make('closed_at')
                            ->label(__('performance_escalations.fields.closed_at'))
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('—'),

                        TextEntry::make('closed_by_user_id')
                            ->label(__('performance_escalations.fields.closed_by_user_id'))
                            ->state(fn (PerformanceEscalation $record): string => $record->closedBy?->name ?? '—'),

                        TextEntry::make('notified_at')
                            ->label(__('performance_escalations.fields.notified_at'))
                            ->dateTime('Y-m-d H:i')
                            ->placeholder('—'),

                        TextEntry::make('close_reason')
                            ->label(__('performance_escalations.fields.close_reason'))
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
