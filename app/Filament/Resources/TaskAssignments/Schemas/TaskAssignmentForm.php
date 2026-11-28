<?php

namespace App\Filament\Resources\TaskAssignments\Schemas;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\TaskAssignmentItem;
use App\Models\TaskAssignmentItemReview;
use App\Services\CompanyFormService;
use App\Services\Tasks\TaskAssignmentItemReviewService;
use App\Services\Tasks\TaskAssignmentStatusResolver;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
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
                            ->inline(false)
                            ->disabled(),

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
                                    ->options(fn (?TaskAssignmentItem $record, Select $component): array => TaskAssignmentStatusResolver::getOptions($record, $component))
                                    ->default(TaskStatus::PENDING->value)
                                    ->required()
                                    ->disabled(fn (?TaskAssignmentItem $record): bool => $record !== null
                                        && ($record->status === TaskStatus::TRANSFERRED
                                            || ($record->requires_review && in_array($record->status, [TaskStatus::COMPLETED, TaskStatus::PENDING_REVIEW], true))))
                                    ->dehydrated(fn (?TaskAssignmentItem $record): bool => $record === null
                                        || ($record->status !== TaskStatus::TRANSFERRED
                                            && (! $record->requires_review || ! in_array($record->status, [TaskStatus::COMPLETED, TaskStatus::PENDING_REVIEW], true))))
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set, ?TaskAssignmentItem $record) {
                                        if ($state === TaskStatus::COMPLETED->value && ($record === null || ! $record->requires_review)) {
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
                                    ->disabled(fn (?TaskAssignmentItem $record): bool => $record === null || $record->requires_review)
                                    ->dehydrated(fn (?TaskAssignmentItem $record): bool => $record !== null && ! $record->requires_review)
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
                                    ->disabled(fn (?TaskAssignmentItem $record): bool => $record !== null && $record->requires_review && $record->due_date !== null)
                                    ->dehydrated(fn (?TaskAssignmentItem $record): bool => $record === null || ! $record->requires_review || $record->due_date === null)
                                    ->helperText(fn (?TaskAssignmentItem $record): ?string => $record?->original_due_date
                                        ? __('tasks.review.labels.extended_from', ['date' => $record->original_due_date->format('Y-m-d')])
                                        : null)
                                    ->columnSpan(2),

                                DatePicker::make('completed_at')
                                    ->label(__('tasks.fields.completed_at'))
                                    ->disabled(fn (?TaskAssignmentItem $record): bool => $record === null || $record->requires_review)
                                    ->dehydrated(fn (?TaskAssignmentItem $record): bool => $record !== null && ! $record->requires_review)
                                    ->columnSpan(2),

                                Select::make('metadata.transferred_to_employee_id')
                                    ->label(__('tasks.fields.transferred_to_employee'))
                                    ->options(fn () => \App\Services\CompanyAccessScopeService::apply(
                                        \App\Models\Employee::query()->nonTemporary()->saudi()->with('user')
                                    )->get()->mapWithKeys(fn (\App\Models\Employee $employee): array => [
                                        $employee->id => $employee->user?->name ?? $employee->employee_number ?? '#'.$employee->id,
                                    ])->all())
                                    ->searchable()
                                    ->preload()
                                    ->required(fn (Get $get) => $get('status') === TaskStatus::TRANSFERRED->value)
                                    ->visible(fn (Get $get) => $get('status') === TaskStatus::TRANSFERRED->value)
                                    ->columnSpan(2),

                                DatePicker::make('metadata.postponed_to_date')
                                    ->label(__('tasks.fields.postponed_to_date'))
                                    ->required(fn (Get $get) => $get('status') === TaskStatus::POSTPONED->value)
                                    ->visible(fn (Get $get) => $get('status') === TaskStatus::POSTPONED->value)
                                    ->columnSpan(2),

                                TextInput::make('notes')
                                    ->label(__('tasks.fields.notes'))
                                    ->maxLength(255)
                                    ->columnSpan(fn (Get $get) => in_array($get('status'), [TaskStatus::TRANSFERRED->value, TaskStatus::POSTPONED->value], true) ? 6 : 2),

                                Placeholder::make('review_summary')
                                    ->label(__('tasks.review.actions.history'))
                                    ->visible(fn (?TaskAssignmentItem $record): bool => $record !== null
                                        && ($record->returned_count > 0 || filled($record->latest_submission_note) || filled($record->latest_return_reason)))
                                    ->content(function (?TaskAssignmentItem $record): string {
                                        if (! $record) {
                                            return '';
                                        }

                                        $parts = [];
                                        if ($record->returned_count > 0) {
                                            $parts[] = __('tasks.review.fields.returned_count').': '.$record->returned_count;
                                        }
                                        if (filled($record->latest_return_reason)) {
                                            $parts[] = __('tasks.review.fields.latest_return_reason').': '.$record->latest_return_reason;
                                        }
                                        if (filled($record->latest_submission_note)) {
                                            $parts[] = __('tasks.review.fields.submission_note').': '.$record->latest_submission_note;
                                        }

                                        return implode(' | ', $parts);
                                    })
                                    ->columnSpanFull(),
                            ])
                            ->extraItemActions([
                                Action::make('approve_item')
                                    ->label(__('tasks.review.actions.approve'))
                                    ->icon('heroicon-o-check-circle')
                                    ->color('success')
                                    ->button()
                                    ->visible(function (array $arguments, Repeater $component): bool {
                                        $item = self::resolveRepeaterItem($arguments, $component);
                                        $user = auth()->user();

                                        return $item !== null && $user !== null && TaskAssignmentItemReviewService::canReview($user, $item);
                                    })
                                    ->beforeFormFilled(self::lockReviewAttempt(...))
                                    ->form(function (array $arguments, Repeater $component): array {
                                        $item = self::resolveRepeaterItem($arguments, $component);

                                        return [
                                            TextInput::make('score')
                                                ->label(__('tasks.review.fields.score'))
                                                ->numeric()
                                                ->required()
                                                ->minValue(0)
                                                ->maxValue($item && (float) $item->weight > 0 ? (float) $item->weight : null)
                                                ->default($item ? (float) $item->weight : 0),
                                            Textarea::make('note')
                                                ->label(__('tasks.review.fields.approval_note'))
                                                ->rows(2),
                                        ];
                                    })
                                    ->action(function (array $data, array $arguments, Repeater $component): void {
                                        $item = self::resolveRepeaterItem($arguments, $component);
                                        $user = auth()->user();
                                        $livewire = $component->getLivewire();
                                        $expectedReviewId = property_exists($livewire, 'lockedReviewId') ? $livewire->lockedReviewId : null;

                                        if (! $item || ! $user || $expectedReviewId === null) {
                                            return;
                                        }

                                        TaskAssignmentItemReviewService::approve(
                                            $user,
                                            $item,
                                            (float) ($data['score'] ?? 0),
                                            $data['note'] ?? null,
                                            $expectedReviewId,
                                        );

                                        if (property_exists($livewire, 'lockedReviewId')) {
                                            $livewire->lockedReviewId = null;
                                        }

                                        if (method_exists($livewire, 'refreshAssignmentItem') && isset($arguments['item'])) {
                                            $livewire->refreshAssignmentItem((string) $arguments['item'], (int) $item->getKey());
                                        }

                                        Notification::make()
                                            ->title(__('tasks.review.notifications.saved_approved'))
                                            ->success()
                                            ->send();
                                    }),

                                Action::make('return_item')
                                    ->label(__('tasks.review.actions.return'))
                                    ->icon('heroicon-o-arrow-uturn-left')
                                    ->color('warning')
                                    ->button()
                                    ->visible(function (array $arguments, Repeater $component): bool {
                                        $item = self::resolveRepeaterItem($arguments, $component);
                                        $user = auth()->user();

                                        return $item !== null && $user !== null && TaskAssignmentItemReviewService::canReview($user, $item);
                                    })
                                    ->beforeFormFilled(self::lockReviewAttempt(...))
                                    ->form([
                                        Select::make('return_category')
                                            ->label(__('tasks.review.fields.return_category'))
                                            ->options([
                                                TaskAssignmentItemReview::RETURN_EXECUTION_DEFECT => __('tasks.review.return_categories.execution_defect'),
                                                TaskAssignmentItemReview::RETURN_SCOPE_CHANGE => __('tasks.review.return_categories.scope_change'),
                                            ])
                                            ->default(TaskAssignmentItemReview::RETURN_EXECUTION_DEFECT)
                                            ->required(),
                                        Textarea::make('reason')
                                            ->label(__('tasks.review.fields.return_reason'))
                                            ->required()
                                            ->rows(3),
                                    ])
                                    ->action(function (array $data, array $arguments, Repeater $component): void {
                                        $item = self::resolveRepeaterItem($arguments, $component);
                                        $user = auth()->user();
                                        $livewire = $component->getLivewire();
                                        $expectedReviewId = property_exists($livewire, 'lockedReviewId') ? $livewire->lockedReviewId : null;

                                        if (! $item || ! $user || $expectedReviewId === null) {
                                            return;
                                        }

                                        TaskAssignmentItemReviewService::returnForRevision(
                                            $user,
                                            $item,
                                            (string) ($data['reason'] ?? ''),
                                            (string) ($data['return_category'] ?? TaskAssignmentItemReview::RETURN_EXECUTION_DEFECT),
                                            $expectedReviewId,
                                        );

                                        if (property_exists($livewire, 'lockedReviewId')) {
                                            $livewire->lockedReviewId = null;
                                        }

                                        if (method_exists($livewire, 'refreshAssignmentItem') && isset($arguments['item'])) {
                                            $livewire->refreshAssignmentItem((string) $arguments['item'], (int) $item->getKey());
                                        }

                                        Notification::make()
                                            ->title(__('tasks.review.notifications.saved_returned'))
                                            ->warning()
                                            ->send();
                                    }),

                                Action::make('extend_due_date_item')
                                    ->label(__('tasks.review.actions.extend_due_date'))
                                    ->icon('heroicon-o-calendar-days')
                                    ->color('info')
                                    ->button()
                                    ->visible(function (array $arguments, Repeater $component): bool {
                                        $item = self::resolveRepeaterItem($arguments, $component);
                                        $user = auth()->user();

                                        return $item !== null && $user !== null && TaskAssignmentItemReviewService::canExtendDueDate($user, $item);
                                    })
                                    ->form(function (array $arguments, Repeater $component): array {
                                        $item = self::resolveRepeaterItem($arguments, $component);

                                        return [
                                            DatePicker::make('new_due_date')
                                                ->label(__('tasks.review.fields.new_due_date'))
                                                ->required()
                                                ->default($item?->due_date?->toDateString() ?? now()->toDateString()),
                                            Textarea::make('reason')
                                                ->label(__('tasks.review.fields.extension_reason'))
                                                ->required()
                                                ->rows(2),
                                        ];
                                    })
                                    ->action(function (array $data, array $arguments, Repeater $component): void {
                                        $item = self::resolveRepeaterItem($arguments, $component);
                                        $user = auth()->user();

                                        if (! $item || ! $user) {
                                            return;
                                        }

                                        TaskAssignmentItemReviewService::extendDueDate(
                                            $user,
                                            $item,
                                            (string) ($data['new_due_date'] ?? ''),
                                            (string) ($data['reason'] ?? ''),
                                        );
                                        $livewire = $component->getLivewire();
                                        if (method_exists($livewire, 'refreshAssignmentItem') && isset($arguments['item'])) {
                                            $livewire->refreshAssignmentItem((string) $arguments['item'], (int) $item->getKey());
                                        }

                                        Notification::make()
                                            ->title(__('tasks.review.notifications.saved_extended'))
                                            ->info()
                                            ->send();
                                    }),
                            ])
                            ->defaultItems(1)
                            ->reorderable()
                            ->collapsible()
                            ->addActionLabel('+ '.__('tasks.single')),
                    ]),
            ]);
    }

    private static function lockReviewAttempt(array $arguments, Repeater $component): void
    {
        $livewire = $component->getLivewire();

        if (property_exists($livewire, 'lockedReviewId')) {
            $item = self::resolveRepeaterItem($arguments, $component);
            $livewire->lockedReviewId = $item ? TaskAssignmentItemReviewService::latestPendingReviewId($item) : null;
        }
    }

    private static function resolveRepeaterItem(array $arguments, Repeater $component): ?TaskAssignmentItem
    {
        $key = $arguments['item'] ?? null;

        return is_string($key) ? $component->getCachedExistingRecords()->get($key) : null;
    }
}
