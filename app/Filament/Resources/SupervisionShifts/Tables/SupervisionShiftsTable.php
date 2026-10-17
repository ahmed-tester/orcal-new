<?php

namespace App\Filament\Resources\SupervisionShifts\Tables;

use App\Filament\Components\OrgSelect;
use App\Models\Employee;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupervisionShiftsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label(__('tasks.supervision_shifts.fields.type'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('tasks.supervision_shifts.types.' . $state) ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'supervision' => 'info',
                        'shift' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('supervisor.user.name')
                    ->label(__('tasks.supervision_shifts.fields.supervisor'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(function ($record) {
                        $parts = [];
                        if ($record->supervisor?->jobTitle?->name) {
                            $parts[] = '💼 ' . $record->supervisor->jobTitle->name;
                        }
                        if ($record->supervisor?->employee_number) {
                            $parts[] = '🆔 ' . $record->supervisor->employee_number;
                        }
                        return !empty($parts) ? implode(' | ', $parts) : null;
                    }),

                OrgSelect::tableColumnCompany('company.trade_name', __('tasks.supervision_shifts.fields.company')),

                TextColumn::make('assigned_team')
                    ->label(__('tasks.supervision_shifts.fields.assigned_employees'))
                    ->state(function ($record) {
                        $ids = $record->assigned_employees ?? [];
                        if (empty($ids)) {
                            return '—';
                        }
                        $names = Employee::whereIn('id', $ids)->with('user')->get()->pluck('user.name')->filter()->toArray();
                        $count = count($names);
                        $preview = implode('، ', array_slice($names, 0, 3)) . ($count > 3 ? '...' : '');
                        return __('tasks.supervision_shifts.table.employees_count', ['count' => $count]) . ': ' . $preview;
                    })
                    ->badge()
                    ->color('primary')
                    ->tooltip(function ($record) {
                        $ids = $record->assigned_employees ?? [];
                        if (empty($ids)) {
                            return null;
                        }
                        $names = Employee::whereIn('id', $ids)->with('user')->get()->pluck('user.name')->filter()->toArray();
                        return implode(' - ', $names);
                    })
                    ->wrap(),

                TextColumn::make('title')
                    ->label(__('tasks.supervision_shifts.fields.task_and_description'))
                    ->state(fn ($record) => $record->task?->name ?? $record->title ?? '—')
                    ->description(fn ($record) => $record->tasks ? \Illuminate\Support\Str::limit($record->tasks, 45) : null)
                    ->searchable()
                    ->wrap()
                    ->url(fn ($record) => route('supervision-shifts.print', $record))
                    ->openUrlInNewTab()
                    ->tooltip(__('tasks.supervision_shifts.actions.print_shift')),

                TextColumn::make('duration')
                    ->label(__('tasks.supervision_shifts.fields.duration'))
                    ->state(function ($record) {
                        if ($record->start_date && $record->end_date) {
                            return $record->start_date->format('Y/m/d') . ' ➔ ' . $record->end_date->format('Y/m/d');
                        } elseif ($record->start_date) {
                            return __('tasks.supervision_shifts.table.from_prefix', ['val' => $record->start_date->format('Y/m/d')]);
                        } elseif ($record->end_date) {
                            return __('tasks.supervision_shifts.table.to_prefix', ['val' => $record->end_date->format('Y/m/d')]);
                        }
                        return __('tasks.supervision_shifts.table.not_specified');
                    })
                    ->description(function ($record) {
                        if ($record->start_date) {
                            $from = \App\Services\HijriDateService::toHijriNumeric($record->start_date);
                            $to = $record->end_date ? ' ➔ ' . \App\Services\HijriDateService::toHijriNumeric($record->end_date) : '';
                            return __('tasks.supervision_shifts.table.hijri_symbol', ['val' => $from . $to]);
                        }
                        return null;
                    })
                    ->color(fn ($state) => $state === __('tasks.supervision_shifts.table.not_specified') ? 'gray' : 'dark')
                    ->toggleable(),

                TextColumn::make('hours')
                    ->label(__('tasks.supervision_shifts.fields.daily_hours'))
                    ->state(function ($record) {
                        if ($record->start_time && $record->end_time) {
                            return substr($record->start_time, 0, 5) . ' ➔ ' . substr($record->end_time, 0, 5);
                        } elseif ($record->start_time) {
                            return __('tasks.supervision_shifts.table.from_prefix', ['val' => substr($record->start_time, 0, 5)]);
                        }
                        return '—';
                    })
                    ->toggleable(),

                TextColumn::make('status')
                    ->label(__('tasks.supervision_shifts.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('tasks.supervision_shifts.statuses.' . $state) ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'active' => 'success',
                        'completed' => 'info',
                        'suspended' => 'warning',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('tasks.supervision_shifts.fields.type'))
                    ->options(__('tasks.supervision_shifts.types')),

                SelectFilter::make('status')
                    ->label(__('tasks.supervision_shifts.fields.status'))
                    ->options(__('tasks.supervision_shifts.statuses')),

                OrgSelect::tableFilterCompany('company_id', __('tasks.supervision_shifts.fields.company')),
            ])
            ->recordActions([
                ActionGroup::make([
                    \Filament\Actions\Action::make('print')
                        ->label(__('tasks.supervision_shifts.actions.print_shift'))
                        ->icon('heroicon-o-printer')
                        ->color('info')
                        ->url(fn ($record) => route('supervision-shifts.print', $record))
                        ->openUrlInNewTab(),
                    ViewAction::make(),
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
