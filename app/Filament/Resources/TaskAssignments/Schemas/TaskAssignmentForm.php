<?php

namespace App\Filament\Resources\TaskAssignments\Schemas;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Services\CompanyFormService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class TaskAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('tasks.sections.assignment_header'))
                    ->description(__('tasks.sections.assignment_header_desc'))
                    ->icon('heroicon-o-user-plus')
                    ->columns(3)
                    ->schema([
                        TextInput::make('transaction_number')
                            ->label(__('tasks.fields.transaction_number'))
                            ->placeholder('Auto Generated')
                            ->disabled()
                            ->maxLength(50),

                        DatePicker::make('assignment_date')
                            ->label(__('tasks.fields.assignment_date'))
                            ->default(now())
                            ->required(),

                        CompanyFormService::makeEmployeeSelect(
                            label: __('tasks.fields.employee'),
                            required: true,
                            saudiOnly: true,
                        ),

                        DatePicker::make('from_date')
                            ->label(__('tasks.fields.from_date')),

                        DatePicker::make('to_date')
                            ->label(__('tasks.fields.to_date')),

                        Toggle::make('is_completed')
                            ->label(__('tasks.fields.is_completed'))
                            ->inline(false),

                        Textarea::make('notes')
                            ->label(__('tasks.fields.notes'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('tasks.sections.tasks_list'))
                    ->description(__('tasks.sections.tasks_list_desc').' - '.__('tasks.helpers.weight_limit_hint'))
                    ->icon('heroicon-o-queue-list')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->columns(6)
                            ->schema([
                                Select::make('task_id')
                                    ->label(__('tasks.fields.task'))
                                    ->relationship('task', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(2)
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($state) {
                                            $task = Task::find($state);
                                            if ($task && $task->default_weight > 0) {
                                                $set('weight', $task->default_weight);
                                            }
                                        }
                                    }),

                                Select::make('status')
                                    ->label(__('tasks.fields.item_status'))
                                    ->options(TaskStatus::class)
                                    ->default(TaskStatus::PENDING->value)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($state === TaskStatus::COMPLETED->value) {
                                            $set('completed_at', now()->toDateString());
                                        }
                                    }),

                                TextInput::make('weight')
                                    ->label(__('tasks.fields.weight'))
                                    ->numeric()
                                    ->default(0.00)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $weight = (float) $state;
                                        $score = (float) $get('score');
                                        $percentage = $weight > 0 ? round(($score / $weight) * 100, 2) : 0.00;
                                        $set('score_percentage', $percentage);
                                    }),

                                TextInput::make('score')
                                    ->label(__('tasks.fields.score'))
                                    ->numeric()
                                    ->default(0.00)
                                    ->minValue(0)
                                    ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0))
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                        $score = (float) $state;
                                        $weight = (float) $get('weight');
                                        $percentage = $weight > 0 ? round(($score / $weight) * 100, 2) : 0.00;
                                        $set('score_percentage', $percentage);
                                    }),

                                TextInput::make('score_percentage')
                                    ->label(__('tasks.fields.score_percentage'))
                                    ->numeric()
                                    ->suffix('%')
                                    ->disabled()
                                    ->dehydrated()
                                    ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0)),

                                DatePicker::make('due_date')
                                    ->label(__('tasks.fields.due_date'))
                                    ->columnSpan(2),

                                DatePicker::make('completed_at')
                                    ->label(__('tasks.fields.completed_at'))
                                    ->columnSpan(2),

                                TextInput::make('notes')
                                    ->label(__('tasks.fields.notes'))
                                    ->maxLength(255)
                                    ->columnSpan(2),
                            ])
                            ->defaultItems(1)
                            ->reorderable()
                            ->collapsible()
                            ->addActionLabel('+ '.__('tasks.single')),
                    ]),
            ]);
    }
}
