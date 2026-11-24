<?php

namespace App\Filament\Resources\Mail\MailMessages\Pages;

use App\Enums\Mail\MailMessageStatus;
use App\Enums\Mail\MailPriority;
use App\Filament\Resources\Mail\MailMessages\MailMessageResource;
use App\Filament\Resources\Mail\MailMessages\Schemas\MailReplyForm;
use App\Filament\Resources\Mail\MailMessages\Schemas\MailReviewForm;
use App\Models\Mail\MailInternalNote;
use App\Models\Mail\MailMessage;
use App\Models\Mail\MailReply;
use App\Services\Mail\MailCategoryService;
use App\Services\Mail\MailMessageService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ViewMailMessage extends ViewRecord
{
    protected static string $resource = MailMessageResource::class;

    public string $newInternalNote = '';

    public bool $isEditingStatus = false;

    public ?string $reviewStatus = null;

    public ?string $reviewPriority = null;

    public ?string $reviewCategory = null;

    public bool $reviewIsImportant = false;

    public int|string|null $reviewAssignedTo = null;

    public ?string $reviewLoadedVersion = null;

    public ?int $lastRepliesCount = null;

    public ?int $lastThreadMessagesCount = null;

    public ?int $lastNotesCount = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if ($this->record instanceof MailMessage && ! $this->record->is_read) {
            $this->record->update(['is_read' => true]);
        }

        $this->syncReviewFields();
        $this->loadCounters();
    }

    public function hydrate(): void
    {
        if (! $this->record instanceof MailMessage) {
            return;
        }

        $previousThread = $this->lastThreadMessagesCount;
        $previousReplies = $this->lastRepliesCount;
        $previousNotes = $this->lastNotesCount;

        $this->record->refresh();
        $this->record->load(['replies.user', 'replies.attachments', 'internalNotes.user', 'regularAttachments', 'account']);

        $currentThread = $this->threadMessagesCount();
        $currentReplies = $this->repliesCount();
        $currentNotes = $this->record->internalNotes()->count();

        if ($previousReplies !== null && $currentReplies > $previousReplies) {
            Notification::make()
                ->title(__('mail_inbox.messages.new_reply_recorded'))
                ->success()
                ->send();
        }

        if ($previousThread !== null && $currentThread > $previousThread) {
            Notification::make()
                ->title(__('mail_inbox.messages.new_thread_message'))
                ->info()
                ->send();
        }

        if ($previousNotes !== null && $currentNotes > $previousNotes) {
            Notification::make()
                ->title(__('mail_inbox.messages.new_internal_note'))
                ->info()
                ->send();
        }

        $this->lastThreadMessagesCount = max($previousThread ?? 0, $currentThread);
        $this->lastRepliesCount = max($previousReplies ?? 0, $currentReplies);
        $this->lastNotesCount = max($previousNotes ?? 0, $currentNotes);

        if (! $this->isEditingStatus) {
            $this->syncReviewFields();
        }
    }

    public function toggleEditStatus(): void
    {
        abort_unless(Gate::allows('review', $this->record), 403);

        $this->isEditingStatus = ! $this->isEditingStatus;

        if ($this->isEditingStatus) {
            $this->syncReviewFields();
        }
    }

    public function cancelStatusReview(): void
    {
        $this->isEditingStatus = false;
        $this->syncReviewFields();
    }

    public function saveStatusReview(): void
    {
        $this->resetErrorBag();

        try {
            app(MailMessageService::class)->updateReview(
                $this->record,
                [
                    'status' => $this->reviewStatus,
                    'priority' => $this->reviewPriority,
                    'category' => $this->reviewCategory,
                    'is_important' => $this->reviewIsImportant,
                    'assigned_to' => filled($this->reviewAssignedTo) ? (int) $this->reviewAssignedTo : null,
                ],
                auth()->user(),
                $this->reviewLoadedVersion
            );
        } catch (ValidationException $e) {
            $map = [
                'status' => 'reviewStatus',
                'priority' => 'reviewPriority',
                'category' => 'reviewCategory',
                'is_important' => 'reviewIsImportant',
                'assigned_to' => 'reviewAssignedTo',
            ];

            foreach ($e->errors() as $field => $messages) {
                $target = $map[$field] ?? $field;
                foreach ($messages as $message) {
                    $this->addError($target, $message);
                }
            }

            return;
        }

        $this->isEditingStatus = false;
        $this->refreshRecord();

        Notification::make()
            ->title(__('mail_inbox.messages.review_updated'))
            ->success()
            ->send();

        $this->redirect(MailMessageResource::getUrl('view', ['record' => $this->record]));
    }

    public function saveInternalNote(): void
    {
        $content = trim($this->newInternalNote);

        if ($content === '') {
            Notification::make()
                ->title(__('mail_inbox.messages.note_required'))
                ->warning()
                ->send();

            return;
        }

        abort_unless(Gate::allows('view', $this->record), 403);

        MailInternalNote::create([
            'company_id' => $this->record->company_id,
            'email_id' => $this->record->id,
            'user_id' => auth()->id(),
            'content' => $content,
        ]);

        $this->newInternalNote = '';
        $this->refreshRecord();

        Notification::make()
            ->title(__('mail_inbox.messages.note_added'))
            ->success()
            ->send();
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            View::make('filament.mail.message-detail')
                ->columnSpanFull()
                ->viewData(fn (): array => [
                    'record' => $this->record,
                    'isEditingStatus' => $this->isEditingStatus,
                    'canReview' => Gate::allows('review', $this->record),
                    'statusOptions' => collect(MailMessageStatus::cases())->mapWithKeys(fn (MailMessageStatus $case): array => [$case->value => $case->getLabel()])->all(),
                    'priorityOptions' => collect(MailPriority::cases())->mapWithKeys(fn (MailPriority $case): array => [$case->value => $case->getLabel()])->all(),
                    'categoryOptions' => app(MailCategoryService::class)->activeOptions($this->record->category),
                    'assigneeOptions' => MailReviewForm::assigneeOptions($this->record),
                    'thread' => $this->threadMessagesQuery()
                        ->with(['replies.user', 'replies.attachments', 'internalNotes.user', 'regularAttachments'])
                        ->orderBy('received_at')
                        ->orderBy('id')
                        ->paginate(25, ['*'], 'threadPage')
                        ->withQueryString(),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('liveStatus')
                ->label(__('mail_inbox.actions.live_status'))
                ->icon('heroicon-m-signal')
                ->color('success')
                ->outlined()
                ->tooltip(__('mail_inbox.tooltips.detail_live_status'))
                ->extraAttributes(['class' => 'cursor-default pointer-events-none opacity-90']),

            MailReplyForm::configure(
                Action::make('reply')
                    ->label(__('mail_inbox.actions.reply'))
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->record($this->record)
                    ->visible(fn (): bool => Gate::allows('reply', $this->record))
            )->after(fn () => $this->refreshRecord()),

            Action::make('add_note')
                ->label(__('mail_inbox.actions.add_note'))
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('gray')
                ->schema([
                    Textarea::make('content')
                        ->label(__('mail_inbox.fields.note_content'))
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    MailInternalNote::create([
                        'company_id' => $this->record->company_id,
                        'email_id' => $this->record->id,
                        'user_id' => auth()->id(),
                        'content' => (string) $data['content'],
                    ]);

                    $this->refreshRecord();

                    Notification::make()
                        ->title(__('mail_inbox.messages.note_added'))
                        ->success()
                        ->send();
                }),

            Action::make('update_review')
                ->label(fn (): string => $this->isEditingStatus
                    ? __('mail_inbox.actions.cancel_edit_status')
                    : __('mail_inbox.actions.edit_status'))
                ->icon(fn (): string => $this->isEditingStatus
                    ? 'heroicon-m-x-mark'
                    : 'heroicon-m-adjustments-horizontal')
                ->color(fn (): string => $this->isEditingStatus ? 'gray' : 'info')
                ->visible(fn (): bool => Gate::allows('review', $this->record))
                ->action(fn () => $this->toggleEditStatus()),

            MailReviewForm::configureMoveAction(
                Action::make('move_to')
                    ->label(__('mail_inbox.actions.move_to'))
                    ->color('gray')
                    ->record($this->record)
            )->after(fn () => $this->redirect(MailMessageResource::getUrl('view', ['record' => $this->record]))),

            MailReviewForm::configureApproveAction(
                Action::make('approve_important')
                    ->icon('heroicon-o-check-badge')
                    ->record($this->record)
            )->after(fn () => $this->redirect(MailMessageResource::getUrl('view', ['record' => $this->record]))),

            Action::make('mark_rejected')
                ->label(__('mail_inbox.actions.mark_rejected'))
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading(__('mail_inbox.modal.reject_heading'))
                ->modalDescription(__('mail_inbox.modal.reject_description'))
                ->visible(fn (): bool => $this->record->status === MailMessageStatus::UNDER_REVIEW && Gate::allows('review', $this->record))
                ->action(function (): void {
                    app(MailMessageService::class)->rejectReview($this->record, auth()->user());

                    Notification::make()
                        ->title(__('mail_inbox.messages.marked_rejected'))
                        ->warning()
                        ->send();

                    $this->redirect(MailMessageResource::getUrl('view', ['record' => $this->record]));
                }),
        ];
    }

    protected function threadMessagesQuery(): Builder
    {
        $query = MailMessageResource::getEloquentQuery()
            ->where('email_account_id', $this->record->email_account_id)
            ->where('company_id', $this->record->company_id);

        if ($this->record->conversation_id) {
            $query->where('conversation_id', $this->record->conversation_id);
        } else {
            $query->whereKey($this->record->id);
        }

        return $query;
    }

    protected function threadMessagesCount(): int
    {
        return $this->threadMessagesQuery()->count();
    }

    protected function repliesCount(): int
    {
        $messageIds = $this->threadMessagesQuery()->pluck('id');
        $messageIds->push($this->record->id);

        return MailReply::query()
            ->whereIn('email_id', $messageIds->unique()->all())
            ->count();
    }

    protected function refreshRecord(): void
    {
        $this->record->refresh();
        $this->record->load(['replies.user', 'replies.attachments', 'internalNotes.user', 'regularAttachments', 'account']);
        $this->syncReviewFields();
        $this->loadCounters();
    }

    protected function loadCounters(): void
    {
        $this->lastThreadMessagesCount = $this->threadMessagesCount();
        $this->lastRepliesCount = $this->repliesCount();
        $this->lastNotesCount = $this->record->internalNotes()->count();
    }

    protected function syncReviewFields(): void
    {
        if (! $this->record instanceof MailMessage) {
            return;
        }

        $this->reviewStatus = $this->record->status?->value;
        $this->reviewPriority = $this->record->priority?->value;
        $this->reviewCategory = $this->record->category;
        $this->reviewIsImportant = (bool) $this->record->is_important;
        $this->reviewAssignedTo = $this->record->assigned_to;
        $this->reviewLoadedVersion = MailMessageService::concurrencyToken($this->record);
    }
}
