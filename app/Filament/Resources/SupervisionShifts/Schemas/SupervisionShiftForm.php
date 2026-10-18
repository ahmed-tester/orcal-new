<?php

namespace App\Filament\Resources\SupervisionShifts\Schemas;

use App\Filament\Components\OrgSelect;
use App\Models\SaudiEmployee;
use App\Services\PhoneService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SupervisionShiftForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('tasks.supervision_shifts.sections.type_and_supervisor'))
                    ->description(__('tasks.supervision_shifts.sections.type_and_supervisor_desc'))
                    ->icon('heroicon-o-user-plus')
                    ->columns(2)
                    ->schema([
                        Radio::make('type')
                            ->label(__('tasks.supervision_shifts.fields.type'))
                            ->options([
                                'supervision' => __('tasks.supervision_shifts.form.type_supervision_option'),
                                'shift' => __('tasks.supervision_shifts.form.type_shift_option'),
                            ])
                            ->default('supervision')
                            ->inline()
                            ->required()
                            ->columnSpanFull(),

                        Select::make('supervisor_id')
                            ->label(fn (callable $get) => $get('type') === 'shift' ? __('tasks.supervision_shifts.form.supervisor_shift_label') : __('tasks.supervision_shifts.form.supervisor_supervision_label'))
                            ->placeholder(__('tasks.supervision_shifts.placeholders.supervisor_select'))
                            ->options(fn () => SaudiEmployee::with(['user', 'jobTitle'])->get()->mapWithKeys(function ($emp) {
                                $name = $emp->user?->name ?? (__('employees.single') . " #{$emp->id}");
                                $job = $emp->jobTitle?->name ? " [{$emp->jobTitle->name}]" : '';
                                $code = $emp->employee_number ? " ({$emp->employee_number})" : '';
                                return [$emp->id => "{$name}{$job}{$code}"];
                            })->toArray())
                            ->searchable()
                            ->preload()
                            ->required(fn (callable $get) => $get('type') === 'supervision')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (empty($state)) {
                                    $set('supervisor_national_id', null);
                                    $set('supervisor_job_title', null);
                                    $set('supervisor_department', null);
                                    $set('supervisor_phone', null);
                                    return;
                                }

                                $emp = SaudiEmployee::with(['user', 'department', 'jobTitle', 'details'])->find($state);
                                if ($emp) {
                                    if ($emp->company_id) {
                                        $set('company_id', $emp->company_id);
                                    }
                                    $set('supervisor_national_id', $emp->details?->national_id ?? '—');
                                    $set('supervisor_job_title', $emp->jobTitle?->name ?? '—');
                                    $set('supervisor_department', $emp->department?->name ?? '—');
                                    $set('supervisor_phone', $emp->user?->phone ?? '—');
                                }
                            })
                            ->afterStateHydrated(function ($state, callable $set) {
                                if (!empty($state)) {
                                    $emp = SaudiEmployee::with(['user', 'department', 'jobTitle', 'details'])->find($state);
                                    if ($emp) {
                                        $set('supervisor_national_id', $emp->details?->national_id ?? '—');
                                        $set('supervisor_job_title', $emp->jobTitle?->name ?? '—');
                                        $set('supervisor_department', $emp->department?->name ?? '—');
                                        $set('supervisor_phone', $emp->user?->phone ?? '—');
                                    }
                                }
                            }),

                        OrgSelect::company('company_id', __('tasks.supervision_shifts.fields.company')),

                        TextInput::make('supervisor_national_id')
                            ->label(__('tasks.supervision_shifts.fields.supervisor_national_id'))
                            ->placeholder(__('tasks.supervision_shifts.form.auto_fetched'))
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('supervisor_job_title')
                            ->label(__('tasks.supervision_shifts.fields.supervisor_job_title'))
                            ->placeholder(__('tasks.supervision_shifts.form.auto_fetched'))
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('supervisor_department')
                            ->label(__('tasks.supervision_shifts.fields.supervisor_department'))
                            ->placeholder(__('tasks.supervision_shifts.form.auto_fetched'))
                            ->disabled()
                            ->dehydrated(false),

                        PhoneService::makeFormField('supervisor_phone', __('tasks.supervision_shifts.fields.supervisor_phone'))
                            ->placeholder(__('tasks.supervision_shifts.form.auto_fetched'))
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Section::make(__('tasks.supervision_shifts.sections.team_and_tasks'))
                    ->description(__('tasks.supervision_shifts.sections.team_and_tasks_desc'))
                    ->icon('heroicon-o-user-group')
                    ->columns(1)
                    ->schema([
                        Select::make('assigned_employees')
                            ->label(fn (callable $get) => $get('type') === 'shift' ? __('tasks.supervision_shifts.form.assigned_shift_label') : __('tasks.supervision_shifts.form.assigned_supervision_label'))
                            ->placeholder(__('tasks.supervision_shifts.placeholders.assigned_select'))
                            ->options(fn () => SaudiEmployee::with(['user', 'jobTitle'])->get()->mapWithKeys(function ($emp) {
                                $name = $emp->user?->name ?? (__('employees.single') . " #{$emp->id}");
                                $job = $emp->jobTitle?->name ? " [{$emp->jobTitle->name}]" : '';
                                $code = $emp->employee_number ? " ({$emp->employee_number})" : '';
                                return [$emp->id => "{$name}{$job}{$code}"];
                            })->toArray())
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->required(fn (callable $get) => $get('type') === 'shift' && empty($get('supervisor_id')))
                            ->helperText(fn (callable $get) => $get('type') === 'shift'
                                ? __('tasks.supervision_shifts.form.assigned_shift_helper')
                                : __('tasks.supervision_shifts.form.assigned_supervision_helper')
                            ),

                        Select::make('task_id')
                            ->label(__('tasks.supervision_shifts.form.task_bank_select'))
                            ->placeholder(__('tasks.supervision_shifts.form.task_bank_placeholder'))
                            ->options(fn () => \App\Models\Task::orderBy('name')->get()->mapWithKeys(function ($t) {
                                $code = $t->task_code ? " ({$t->task_code})" : '';
                                return [$t->id => "{$t->name}{$code}"];
                            })->toArray())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!empty($state) && $task = \App\Models\Task::find($state)) {
                                    $set('title', $task->name);
                                    if (!empty($task->description)) {
                                        $set('tasks', $task->description);
                                    }
                                }
                            })
                            ->helperText(__('tasks.supervision_shifts.form.task_bank_helper')),

                        TextInput::make('title')
                            ->label(__('tasks.supervision_shifts.form.task_title_label'))
                            ->placeholder(__('tasks.supervision_shifts.form.task_title_placeholder'))
                            ->maxLength(255),

                        Textarea::make('tasks')
                            ->label(__('tasks.supervision_shifts.form.task_desc_label'))
                            ->placeholder(__('tasks.supervision_shifts.form.task_desc_placeholder'))
                            ->rows(3),
                    ]),

                Section::make(__('tasks.supervision_shifts.form.schedule_section_title'))
                    ->description(__('tasks.supervision_shifts.form.schedule_section_desc'))
                    ->icon('heroicon-o-calendar-days')
                    ->columns(4)
                    ->schema([
                        DatePicker::make('start_date')
                            ->label(__('tasks.supervision_shifts.fields.start_date'))
                            ->placeholder(__('tasks.supervision_shifts.form.start_date_placeholder')),

                        DatePicker::make('end_date')
                            ->label(__('tasks.supervision_shifts.fields.end_date'))
                            ->placeholder(__('tasks.supervision_shifts.form.end_date_placeholder')),

                        TimePicker::make('start_time')
                            ->label(__('tasks.supervision_shifts.fields.start_time'))
                            ->placeholder(__('tasks.supervision_shifts.form.start_time_placeholder'))
                            ->seconds(false),

                        TimePicker::make('end_time')
                            ->label(__('tasks.supervision_shifts.fields.end_time'))
                            ->placeholder(__('tasks.supervision_shifts.form.end_time_placeholder'))
                            ->seconds(false),
                    ]),

                Section::make(__('tasks.supervision_shifts.sections.status_and_notes'))
                    ->icon('heroicon-o-clipboard-document-check')
                    ->columns(2)
                    ->schema([
                        Select::make('status')
                            ->label(__('tasks.supervision_shifts.fields.status'))
                            ->options(__('tasks.supervision_shifts.statuses'))
                            ->default('active')
                            ->required(),

                        Textarea::make('notes')
                            ->label(__('tasks.supervision_shifts.fields.notes'))
                            ->placeholder(__('tasks.supervision_shifts.form.notes_placeholder'))
                            ->rows(2),
                    ]),
            ]);
    }
}
