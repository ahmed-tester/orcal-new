<?php

namespace App\Filament\Resources\Mail\MailMessages\Tables;

use App\Enums\Mail\MailMessageStatus;
use App\Enums\Mail\MailPriority;
use App\Enums\Mail\MailSourceFolder;
use App\Filament\Resources\Mail\MailMessages\MailMessageResource;
use App\Filament\Resources\Mail\MailMessages\Pages\ViewMailMessage;
use App\Filament\Resources\Mail\MailMessages\Schemas\MailReplyForm;
use App\Filament\Resources\Mail\MailMessages\Schemas\MailReviewForm;
use App\Models\Mail\MailAccount;
use App\Models\Mail\MailMessage;
use App\Services\Mail\MailAccessService;
use App\Services\Mail\MailCategoryService;
use App\Services\Mail\MailMessageService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class MailMessagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->recordUrl(fn (MailMessage $record): string => ViewMailMessage::getUrl([$record->id]))
            ->defaultSort('id', 'desc')
            ->searchPlaceholder(__('mail_inbox.fields.search_placeholder'))
            ->columns([
                IconColumn::make('is_read')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-envelope-open')
                    ->falseIcon('heroicon-s-envelope')
                    ->trueColor('gray')
                    ->falseColor('warning')
                    ->tooltip(fn (MailMessage $record): string => $record->is_read ? __('mail_inbox.tooltips.read') : __('mail_inbox.tooltips.unread')),

                TextColumn::make('sender_email')
                    ->label(__('mail_inbox.fields.sender'))
                    ->formatStateUsing(fn (MailMessage $record): string => $record->sender_name ?: $record->sender_email)
                    ->description(fn (MailMessage $record): string => ($record->sender_name ? $record->sender_email.' • ' : '').__('mail_inbox.fields.to').': '.$record->recipient_email)
                    ->searchable(['sender_email', 'sender_name', 'recipient_email'])
                    ->sortable()
                    ->weight('semibold')
                    ->wrap(false),

                TextColumn::make('subject')
                    ->label(__('mail_inbox.fields.subject_and_content'))
                    ->grow()
                    ->icon(fn (MailMessage $record): ?string => ($record->conversation_id && $record->conversation?->messages()->count() > 1) ? 'heroicon-m-chat-bubble-left-right' : null)
                    ->iconColor('info')
                    ->tooltip(function (MailMessage $record): ?string {
                        $tooltipParts = [];

                        $threadCount = $record->conversation_id ? $record->conversation?->messages()->count() : 0;
                        if ($threadCount > 1) {
                            $tooltipParts[] = __('mail_inbox.tooltips.thread_connected', ['count' => $threadCount]);
                        }

                        $tooltipParts[] = __('mail_inbox.tooltips.subject', ['subject' => $record->subject]);

                        if ($record->summary) {
                            $tooltipParts[] = __('mail_inbox.tooltips.summary', ['summary' => $record->summary]);
                        }

                        $body = trim(strip_tags((string) ($record->body_plain ?: $record->body_html)));
                        if (! empty($body)) {
                            $cleanBody = preg_replace('/\s+/', ' ', $body);
                            $tooltipParts[] = __('mail_inbox.tooltips.body', ['body' => mb_substr($cleanBody, 0, 350).(mb_strlen($cleanBody) > 350 ? '...' : '')]);
                        }

                        return implode("\n\n", $tooltipParts);
                    })
                    ->description(function (MailMessage $record): ?string {
                        $threadPrefix = '';
                        $threadCount = $record->conversation_id ? $record->conversation?->messages()->count() : 0;
                        if ($threadCount > 1) {
                            $threadPrefix = __('mail_inbox.fields.thread_prefix', ['count' => $threadCount]);
                        }

                        if ($record->summary) {
                            return $threadPrefix.mb_substr($record->summary, 0, 65).(mb_strlen($record->summary) > 65 ? '...' : '');
                        }
                        $body = trim(strip_tags((string) ($record->body_plain ?: $record->body_html)));
                        if (! empty($body)) {
                            $snippet = preg_replace('/\s+/', ' ', $body);

                            return $threadPrefix.__('mail_inbox.fields.content_prefix').mb_substr($snippet, 0, 65).(mb_strlen($snippet) > 65 ? '...' : '');
                        }

                        return $threadPrefix ?: null;
                    })
                    ->searchable(['subject', 'body_plain', 'body_html', 'summary', 'classification_reason'])
                    ->lineClamp(1),

                IconColumn::make('has_attachments')
                    ->label(__('mail_inbox.fields.attachments'))
                    ->state(fn (MailMessage $record): bool => $record->regularAttachments()->exists())
                    ->icon(fn (bool $state): ?string => $state ? 'heroicon-m-paper-clip' : null)
                    ->color('primary')
                    ->tooltip(function (MailMessage $record): ?string {
                        $count = $record->regularAttachments()->count();

                        return $count > 0 ? __('mail_inbox.tooltips.attachments_count', ['count' => $count]) : null;
                    }),

                TextColumn::make('priority')
                    ->label(__('mail_inbox.fields.priority'))
                    ->badge(),

                TextColumn::make('status')
                    ->label(__('mail_inbox.fields.status'))
                    ->badge(),

                TextColumn::make('category')
                    ->label(__('mail_inbox.fields.category'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state, MailMessage $record): string => $record->categoryLabel())
                    ->color(fn (MailMessage $record): string => $record->categoryColor()),

                TextColumn::make('confidence')
                    ->label(__('mail_inbox.fields.confidence'))
                    ->formatStateUsing(fn ($state): string => filled($state) ? $state.'%' : '-')
                    ->badge()
                    ->color('warning')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('classification_reason')
                    ->label(__('mail_inbox.fields.classification_reason'))
                    ->limit(45)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('received_at')
                    ->label(__('mail_inbox.fields.date'))
                    ->since()
                    ->sortable(),

                TextColumn::make('account.email')
                    ->label(__('mail_inbox.fields.recipient'))
                    ->badge()
                    ->color('primary')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('source_folder')
                    ->label(__('mail_inbox.fields.source'))
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('date_period')
                    ->label(__('mail_inbox.fields.date_period'))
                    ->options(fn (): array => __('mail_inbox.date_periods'))
                    ->query(function (Builder $query, array $data): Builder {
                        $val = $data['value'] ?? null;
                        if (! $val) {
                            return $query;
                        }

                        return match ($val) {
                            'today' => $query->whereDate('received_at', Carbon::today()),
                            'yesterday' => $query->whereDate('received_at', Carbon::yesterday()),
                            'last_7_days' => $query->where('received_at', '>=', Carbon::now()->subDays(7)),
                            'this_week' => $query->whereBetween('received_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]),
                            'this_month' => $query->whereBetween('received_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]),
                            'last_month' => $query->whereBetween('received_at', [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()]),
                            'last_30_days' => $query->where('received_at', '>=', Carbon::now()->subDays(30)),
                            default => $query,
                        };
                    }),

                Filter::make('date_range')
                    ->label(__('mail_inbox.fields.date_range'))
                    ->form([
                        DatePicker::make('received_from')
                            ->label(__('mail_inbox.fields.received_from'))
                            ->maxDate(now()),
                        DatePicker::make('received_until')
                            ->label(__('mail_inbox.fields.received_until'))
                            ->maxDate(now()),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['received_from'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate('received_at', '>=', $date),
                            )
                            ->when(
                                $data['received_until'] ?? null,
                                fn (Builder $q, $date): Builder => $q->whereDate('received_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (! empty($data['received_from'])) {
                            $indicators['received_from'] = __('mail_inbox.fields.received_from').': '.Carbon::parse($data['received_from'])->format('Y-m-d');
                        }
                        if (! empty($data['received_until'])) {
                            $indicators['received_until'] = __('mail_inbox.fields.received_until').': '.Carbon::parse($data['received_until'])->format('Y-m-d');
                        }

                        return $indicators;
                    }),

                TernaryFilter::make('is_read')
                    ->label(__('mail_inbox.fields.read_status'))
                    ->placeholder(__('mail_inbox.fields.all_filter'))
                    ->trueLabel(__('mail_inbox.fields.read_only'))
                    ->falseLabel(__('mail_inbox.fields.unread_only')),

                TernaryFilter::make('replied')
                    ->label(__('mail_inbox.fields.reply_status'))
                    ->placeholder(__('mail_inbox.fields.all_filter'))
                    ->trueLabel(__('mail_inbox.fields.replied_only'))
                    ->falseLabel(__('mail_inbox.fields.unreplied_only'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('replied_at'),
                        false: fn (Builder $query) => $query->whereNull('replied_at'),
                    ),

                SelectFilter::make('email_account_id')
                    ->label(__('mail_inbox.fields.account_filter'))
                    ->options(function (): array {
                        $user = auth()->user();

                        return MailAccessService::scopeAccounts(MailAccount::query(), $user)
                            ->pluck('email', 'id')
                            ->all();
                    }),

                SelectFilter::make('status')
                    ->label(__('mail_inbox.fields.status_filter'))
                    ->options(MailMessageStatus::class),

                SelectFilter::make('priority')
                    ->label(__('mail_inbox.fields.priority_filter'))
                    ->options(MailPriority::class),

                SelectFilter::make('category')
                    ->label(__('mail_inbox.fields.category_filter'))
                    ->options(fn (): array => app(MailCategoryService::class)->allFilterOptions()),

                SelectFilter::make('source_folder')
                    ->label(__('mail_inbox.fields.source_folder_filter'))
                    ->options(MailSourceFolder::class),

                Filter::make('threaded')
                    ->label(__('mail_inbox.fields.threaded_filter'))
                    ->query(fn (Builder $query): Builder => $query->whereHas('conversation', fn (Builder $q) => $q->has('messages', '>', 1))),
            ])
            ->filtersFormColumns(2)
            ->recordActions([
                ViewAction::make()
                    ->label(__('mail_inbox.actions.view'))
                    ->icon('heroicon-m-eye')
                    ->button()
                    ->size('xs')
                    ->color('gray')
                    ->after(function (MailMessage $record): void {
                        if (! $record->is_read) {
                            $record->update(['is_read' => true]);
                        }
                    }),

                ActionGroup::make([
                    MailReplyForm::configure(
                        Action::make('reply')
                            ->label(__('mail_inbox.actions.reply_to_client'))
                            ->icon('heroicon-m-arrow-uturn-left')
                            ->color('success')
                            ->visible(fn (MailMessage $record): bool => Gate::allows('reply', $record))
                    ),

                    Action::make('viewThread')
                        ->label(__('mail_inbox.actions.view_thread'))
                        ->icon('heroicon-m-chat-bubble-left-right')
                        ->color('info')
                        ->visible(fn (MailMessage $record): bool => (bool) ($record->conversation_id && $record->conversation?->messages()->count() > 1))
                        ->modalHeading(fn (MailMessage $record): string => __('mail_inbox.modal.thread_heading', ['subject' => $record->subject]))
                        ->modalDescription(fn (MailMessage $record): string => __('mail_inbox.modal.thread_description', ['count' => $record->conversation?->messages()->count() ?? 0]))
                        ->modalContent(function (MailMessage $record) {
                            $query = MailMessage::query()
                                ->where('email_account_id', $record->email_account_id)
                                ->where('company_id', $record->company_id);

                            if ($record->conversation_id) {
                                $query->where('conversation_id', $record->conversation_id);
                            } else {
                                $query->whereKey($record->id);
                            }

                            $thread = $query
                                ->with(['regularAttachments', 'replies.user', 'replies.attachments'])
                                ->orderBy('received_at')
                                ->orderBy('id')
                                ->paginate(10, ['*'], 'threadModalPage')
                                ->withQueryString();

                            return view('filament.mail.conversation-thread-modal', [
                                'thread' => $thread,
                                'currentId' => $record->id,
                            ]);
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel(__('mail_inbox.actions.close'))
                        ->modalWidth('4xl'),

                    MailReviewForm::configure(
                        Action::make('update_review')
                            ->label(__('mail_inbox.actions.update_review'))
                            ->color('warning')
                    )->after(fn ($livewire) => $livewire->redirect(request()->header('Referer') ?: MailMessageResource::getUrl('index'))),

                    MailReviewForm::configureMoveAction(
                        Action::make('move_to')
                            ->label(__('mail_inbox.actions.move_to'))
                            ->color('info')
                    )->after(fn ($livewire) => $livewire->redirect(request()->header('Referer') ?: MailMessageResource::getUrl('index'))),

                    MailReviewForm::configureApproveAction(
                        Action::make('approve_review')
                            ->icon('heroicon-m-check-badge')
                    )->after(fn ($livewire) => $livewire->redirect(request()->header('Referer') ?: MailMessageResource::getUrl('index'))),

                    Action::make('reject_review')
                        ->label(__('mail_inbox.actions.mark_rejected'))
                        ->icon('heroicon-m-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(__('mail_inbox.modal.reject_heading'))
                        ->modalDescription(__('mail_inbox.modal.reject_description'))
                        ->visible(fn (MailMessage $record): bool => $record->status === MailMessageStatus::UNDER_REVIEW && Gate::allows('review', $record))
                        ->action(function (MailMessage $record): void {
                            app(MailMessageService::class)->rejectReview($record, auth()->user());
                            Notification::make()->title(__('mail_inbox.messages.marked_rejected'))->warning()->send();
                        })
                        ->after(fn ($livewire) => $livewire->redirect(request()->header('Referer') ?: MailMessageResource::getUrl('index'))),

                    Action::make('addNote')
                        ->label(__('mail_inbox.actions.add_note'))
                        ->icon('heroicon-m-chat-bubble-bottom-center-text')
                        ->color('gray')
                        ->schema([
                            Textarea::make('content')
                                ->label(__('mail_inbox.fields.internal_note_secret'))
                                ->rows(4)
                                ->required(),
                        ])
                        ->action(function (MailMessage $record, array $data): void {
                            $record->internalNotes()->create([
                                'company_id' => $record->company_id,
                                'user_id' => auth()->id(),
                                'content' => $data['content'],
                            ]);

                            Notification::make()
                                ->title(__('mail_inbox.messages.note_added'))
                                ->success()
                                ->send();
                        }),

                    DeleteAction::make()
                        ->visible(fn (MailMessage $record): bool => Gate::allows('delete', $record)),
                ])
                    ->tooltip(__('mail_inbox.actions.more_options'))
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('xs'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markAsRead')
                        ->label(__('mail_inbox.actions.mark_as_read'))
                        ->icon('heroicon-m-check')
                        ->action(fn ($records) => $records->each(fn (MailMessage $record) => $record->update(['is_read' => true]))),

                    BulkAction::make('moveToImportant')
                        ->label(__('mail_inbox.actions.move_to_important'))
                        ->icon('heroicon-m-star')
                        ->color('warning')
                        ->visible(function (): bool {
                            $user = auth()->user();

                            return $user !== null && (MailAccessService::canAdministerAllMail($user) || $user->can(MailAccessService::PERM_REVIEW));
                        })
                        ->schema([
                            Select::make('category')
                                ->label(__('mail_inbox.fields.replacement_category_if_excluded'))
                                ->options(fn (): array => app(MailCategoryService::class)->nonExcludedActiveOptions())
                                ->helperText(__('mail_inbox.helpers.bulk_replacement_category')),
                        ])
                        ->action(function ($records, array $data): void {
                            $count = app(MailMessageService::class)->moveMessagesBulk(
                                $records,
                                MailMessageService::TAB_IMPORTANT,
                                auth()->user(),
                                $data['category'] ?? null
                            );

                            Notification::make()
                                ->title(__('mail_inbox.messages.bulk_moved', ['count' => $count]))
                                ->success()
                                ->send();
                        })
                        ->after(fn ($livewire) => $livewire->redirect(request()->header('Referer') ?: MailMessageResource::getUrl('index'))),

                    BulkAction::make('markAsUnderReview')
                        ->label(__('mail_inbox.actions.mark_as_under_review'))
                        ->icon('heroicon-m-question-mark-circle')
                        ->visible(function (): bool {
                            $user = auth()->user();

                            return $user !== null && (MailAccessService::canAdministerAllMail($user) || $user->can(MailAccessService::PERM_REVIEW));
                        })
                        ->schema([
                            Select::make('category')
                                ->label(__('mail_inbox.fields.replacement_category_if_excluded'))
                                ->options(fn (): array => app(MailCategoryService::class)->nonExcludedActiveOptions())
                                ->helperText(__('mail_inbox.helpers.bulk_replacement_category')),
                        ])
                        ->action(function ($records, array $data): void {
                            $count = app(MailMessageService::class)->moveMessagesBulk(
                                $records,
                                MailMessageService::TAB_UNDER_REVIEW,
                                auth()->user(),
                                $data['category'] ?? null
                            );

                            Notification::make()
                                ->title(__('mail_inbox.messages.bulk_moved', ['count' => $count]))
                                ->success()
                                ->send();
                        })
                        ->after(fn ($livewire) => $livewire->redirect(request()->header('Referer') ?: MailMessageResource::getUrl('index'))),

                    BulkAction::make('moveToExcluded')
                        ->label(__('mail_inbox.actions.move_to_excluded'))
                        ->icon('heroicon-m-no-symbol')
                        ->color('danger')
                        ->visible(function (): bool {
                            $user = auth()->user();

                            return $user !== null && (MailAccessService::canAdministerAllMail($user) || $user->can(MailAccessService::PERM_REVIEW));
                        })
                        ->schema([
                            Select::make('category')
                                ->label(__('mail_inbox.fields.excluded_category_optional'))
                                ->options(fn (): array => app(MailCategoryService::class)->excludedActiveOptions()),
                        ])
                        ->action(function ($records, array $data): void {
                            $count = app(MailMessageService::class)->moveMessagesBulk(
                                $records,
                                MailMessageService::TAB_SPAM_EXCLUDED,
                                auth()->user(),
                                $data['category'] ?? null
                            );

                            Notification::make()
                                ->title(__('mail_inbox.messages.bulk_moved', ['count' => $count]))
                                ->warning()
                                ->send();
                        })
                        ->after(fn ($livewire) => $livewire->redirect(request()->header('Referer') ?: MailMessageResource::getUrl('index'))),

                    BulkAction::make('moveToNormal')
                        ->label(__('mail_inbox.actions.move_to_normal'))
                        ->icon('heroicon-m-inbox')
                        ->visible(function (): bool {
                            $user = auth()->user();

                            return $user !== null && (MailAccessService::canAdministerAllMail($user) || $user->can(MailAccessService::PERM_REVIEW));
                        })
                        ->schema([
                            Select::make('category')
                                ->label(__('mail_inbox.fields.replacement_category_if_excluded'))
                                ->options(fn (): array => app(MailCategoryService::class)->nonExcludedActiveOptions())
                                ->helperText(__('mail_inbox.helpers.bulk_replacement_category')),
                        ])
                        ->action(function ($records, array $data): void {
                            $count = app(MailMessageService::class)->moveMessagesBulk(
                                $records,
                                MailMessageService::TAB_NORMAL,
                                auth()->user(),
                                $data['category'] ?? null
                            );

                            Notification::make()
                                ->title(__('mail_inbox.messages.bulk_moved', ['count' => $count]))
                                ->success()
                                ->send();
                        })
                        ->after(fn ($livewire) => $livewire->redirect(request()->header('Referer') ?: MailMessageResource::getUrl('index'))),

                    BulkAction::make('changeCategory')
                        ->label(__('mail_inbox.actions.change_category'))
                        ->icon('heroicon-m-tag')
                        ->visible(function (): bool {
                            $user = auth()->user();

                            return $user !== null && (MailAccessService::canAdministerAllMail($user) || $user->can(MailAccessService::PERM_REVIEW));
                        })
                        ->schema([
                            Select::make('category')
                                ->label(__('mail_inbox.fields.category'))
                                ->options(fn (): array => app(MailCategoryService::class)->activeOptions())
                                ->required(),
                        ])
                        ->action(function ($records, array $data): void {
                            $count = app(MailMessageService::class)->moveMessagesBulk(
                                $records,
                                MailMessageService::ACTION_CHANGE_CATEGORY,
                                auth()->user(),
                                (string) $data['category']
                            );

                            Notification::make()
                                ->title(__('mail_inbox.messages.bulk_moved', ['count' => $count]))
                                ->success()
                                ->send();
                        })
                        ->after(fn ($livewire) => $livewire->redirect(request()->header('Referer') ?: MailMessageResource::getUrl('index'))),

                    DeleteBulkAction::make()
                        ->visible(fn (): bool => Gate::allows('deleteAny', MailMessage::class)),
                ]),
            ]);
    }
}
