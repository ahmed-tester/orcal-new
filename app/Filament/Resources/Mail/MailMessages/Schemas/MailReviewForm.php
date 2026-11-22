<?php

namespace App\Filament\Resources\Mail\MailMessages\Schemas;

use App\Enums\Mail\MailMessageStatus;
use App\Enums\Mail\MailPriority;
use App\Models\Mail\MailMessage;
use App\Models\User;
use App\Services\Mail\MailAccessService;
use App\Services\Mail\MailCategoryService;
use App\Services\Mail\MailMessageService;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Gate;

class MailReviewForm
{
    /**
     * Shared review/status update action used by the message table.
     */
    public static function configure(Action $action): Action
    {
        return $action
            ->icon('heroicon-m-adjustments-horizontal')
            ->visible(fn (MailMessage $record): bool => Gate::allows('review', $record))
            ->fillForm(fn (MailMessage $record): array => [
                'status' => $record->status?->value,
                'priority' => $record->priority?->value,
                'category' => $record->category,
                'is_important' => (bool) $record->is_important,
                'assigned_to' => $record->assigned_to,
                'expected_version' => MailMessageService::concurrencyToken($record),
            ])
            ->schema([
                Hidden::make('expected_version'),
                Select::make('status')
                    ->label(__('mail_inbox.fields.status'))
                    ->options(MailMessageStatus::class)
                    ->required(),
                Select::make('priority')
                    ->label(__('mail_inbox.fields.priority'))
                    ->options(MailPriority::class)
                    ->required(),
                Select::make('category')
                    ->label(__('mail_inbox.fields.category'))
                    ->options(fn (MailMessage $record): array => app(MailCategoryService::class)->activeOptions($record->category))
                    ->required(),
                Toggle::make('is_important')
                    ->label(__('mail_inbox.fields.is_important'))
                    ->helperText(__('mail_inbox.helpers.priority_vs_importance')),
                Select::make('assigned_to')
                    ->label(__('mail_inbox.fields.assignee'))
                    ->options(fn (MailMessage $record): array => static::assigneeOptions($record))
                    ->searchable(),
            ])
            ->action(function (MailMessage $record, array $data): void {
                app(MailMessageService::class)->updateReview(
                    $record,
                    $data,
                    auth()->user(),
                    isset($data['expected_version']) ? (string) $data['expected_version'] : null
                );

                Notification::make()
                    ->title(__('mail_inbox.messages.review_updated'))
                    ->success()
                    ->send();
            });
    }

    /**
     * Shared quick-move action used by the message table and details page.
     */
    public static function configureMoveAction(Action $action): Action
    {
        return $action
            ->icon('heroicon-m-arrows-right-left')
            ->visible(fn (MailMessage $record): bool => Gate::allows('review', $record))
            ->modalWidth('md')
            ->schema([
                Select::make('destination')
                    ->label(__('mail_inbox.fields.move_destination'))
                    ->options(static::destinationOptions())
                    ->live()
                    ->required(),
                Select::make('category')
                    ->label(__('mail_inbox.fields.category'))
                    ->options(function (Get $get): array {
                        $dest = (string) $get('destination');
                        $service = app(MailCategoryService::class);

                        return match ($dest) {
                            MailMessageService::TAB_IMPORTANT,
                            MailMessageService::TAB_UNDER_REVIEW,
                            MailMessageService::TAB_NORMAL => $service->nonExcludedActiveOptions(),
                            MailMessageService::TAB_SPAM_EXCLUDED => $service->excludedActiveOptions(),
                            default => $service->activeOptions(),
                        };
                    })
                    ->required(function (MailMessage $record, Get $get): bool {
                        $dest = (string) $get('destination');
                        if ($dest === MailMessageService::ACTION_CHANGE_CATEGORY) {
                            return true;
                        }

                        return in_array($dest, [
                            MailMessageService::TAB_IMPORTANT,
                            MailMessageService::TAB_UNDER_REVIEW,
                            MailMessageService::TAB_NORMAL,
                        ], true) && app(MailCategoryService::class)->isExcluded($record->category);
                    })
                    ->visible(function (MailMessage $record, Get $get): bool {
                        $dest = (string) $get('destination');
                        if ($dest === MailMessageService::ACTION_CHANGE_CATEGORY) {
                            return true;
                        }

                        return in_array($dest, [
                            MailMessageService::TAB_IMPORTANT,
                            MailMessageService::TAB_UNDER_REVIEW,
                            MailMessageService::TAB_NORMAL,
                        ], true) && app(MailCategoryService::class)->isExcluded($record->category);
                    }),
                Placeholder::make('impact_preview')
                    ->label(__('mail_inbox.fields.move_impact'))
                    ->content(function (MailMessage $record, Get $get): string {
                        return static::describeImpact(
                            (string) $get('destination'),
                            $get('category') ? (string) $get('category') : null,
                            1,
                            app(MailCategoryService::class)->isExcluded($record->category)
                        );
                    })
                    ->visible(fn (Get $get): bool => filled($get('destination'))),
            ])
            ->action(function (MailMessage $record, array $data): void {
                app(MailMessageService::class)->moveMessage(
                    $record,
                    (string) $data['destination'],
                    auth()->user(),
                    $data['category'] ?? null
                );

                Notification::make()
                    ->title(__('mail_inbox.messages.message_moved'))
                    ->success()
                    ->send();
            });
    }

    /**
     * Shared approve-as-important action that prompts for a replacement category
     * when the under-review message currently has an excluded category.
     */
    public static function configureApproveAction(Action $action): Action
    {
        return $action
            ->label(__('mail_inbox.actions.approve_important'))
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(__('mail_inbox.modal.approve_heading'))
            ->modalDescription(__('mail_inbox.modal.approve_description'))
            ->visible(fn (MailMessage $record): bool => $record->status === MailMessageStatus::UNDER_REVIEW && Gate::allows('review', $record))
            ->schema([
                Select::make('category')
                    ->label(__('mail_inbox.fields.replacement_category_if_excluded'))
                    ->options(fn (): array => app(MailCategoryService::class)->nonExcludedActiveOptions())
                    ->required(fn (MailMessage $record): bool => app(MailCategoryService::class)->isExcluded($record->category))
                    ->visible(fn (MailMessage $record): bool => app(MailCategoryService::class)->isExcluded($record->category)),
            ])
            ->action(function (MailMessage $record, array $data): void {
                app(MailMessageService::class)->approveReview(
                    $record,
                    auth()->user(),
                    isset($data['category']) ? (string) $data['category'] : null
                );

                Notification::make()
                    ->title(__('mail_inbox.messages.marked_important'))
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array<string, string>
     */
    public static function destinationOptions(): array
    {
        return [
            MailMessageService::TAB_IMPORTANT => __('mail_inbox.destinations.important'),
            MailMessageService::TAB_UNDER_REVIEW => __('mail_inbox.destinations.under_review'),
            MailMessageService::TAB_SPAM_EXCLUDED => __('mail_inbox.destinations.spam_excluded'),
        ];
    }

    public static function describeImpact(string $destination, ?string $category, int $count, bool $hasExcluded = false): string
    {
        if ($destination === MailMessageService::ACTION_CHANGE_CATEGORY && $category !== null) {
            $isExcluded = app(MailCategoryService::class)->isExcluded($category);

            return $isExcluded
                ? __('mail_inbox.helpers.move_impact_excluded_category', ['count' => $count])
                : __('mail_inbox.helpers.move_impact_normal_category', ['count' => $count]);
        }

        if ($hasExcluded && in_array($destination, [
            MailMessageService::TAB_IMPORTANT,
            MailMessageService::TAB_UNDER_REVIEW,
            MailMessageService::TAB_NORMAL,
        ], true)) {
            return __('mail_inbox.helpers.move_impact_requires_replacement', ['count' => $count]);
        }

        return __("mail_inbox.helpers.move_impact_{$destination}", ['count' => $count]);
    }

    public static function assigneeOptions(MailMessage $record): array
    {
        $employeeUserIds = $record->account?->employees()
            ->where('employees.status', 'active')
            ->pluck('employees.user_id') ?? collect();

        return User::whereIn('id', $employeeUserIds)
            ->where('status', 'active')
            ->get()
            ->filter(fn (User $user): bool => MailAccessService::canViewMessage($user, $record))
            ->pluck('name', 'id')
            ->all();
    }
}
