<?php

namespace App\Filament\Resources\Mail\MailMessages\Pages;

use App\Enums\Mail\MailAccountStatus;
use App\Enums\Mail\MailCategory;
use App\Enums\Mail\MailMessageStatus;
use App\Filament\Resources\Mail\MailMessages\MailMessageResource;
use App\Models\Mail\MailAccount;
use App\Models\Mail\MailMessage;
use App\Services\Mail\MailAccessService;
use App\Services\Mail\MailSyncService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListMailMessages extends ListRecords
{
    protected static string $resource = MailMessageResource::class;

    public ?int $lastKnownEmailId = null;

    public function mount(): void
    {
        parent::mount();
        $this->lastKnownEmailId = (int) (MailMessageResource::getEloquentQuery()->max('id') ?? 0);
    }

    public function hydrate(): void
    {
        if ($this->lastKnownEmailId !== null) {
            $latestEmail = MailMessageResource::getEloquentQuery()->latest('id')->first();
            if ($latestEmail && $latestEmail->id > $this->lastKnownEmailId) {
                $newCount = MailMessageResource::getEloquentQuery()->where('id', '>', $this->lastKnownEmailId)->count();
                $this->lastKnownEmailId = (int) $latestEmail->id;

                Notification::make()
                    ->title(__('mail_inbox.messages.new_mail_arrived', ['count' => $newCount]))
                    ->body($latestEmail->subject.' - '.__('mail_inbox.messages.new_mail_from', ['sender' => $latestEmail->sender_name ?: $latestEmail->sender_email]))
                    ->success()
                    ->send();
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('liveStatus')
                ->label(__('mail_inbox.actions.live_status'))
                ->icon('heroicon-m-signal')
                ->color('success')
                ->outlined()
                ->tooltip(__('mail_inbox.tooltips.live_status'))
                ->extraAttributes(['class' => 'cursor-default pointer-events-none opacity-90']),

            Action::make('syncAll')
                ->label(__('mail_inbox.actions.sync_all'))
                ->icon('heroicon-m-arrow-path')
                ->color('primary')
                ->visible(function (): bool {
                    $user = auth()->user();

                    return $user !== null && (MailAccessService::canAdministerAllMail($user) || $user->can(MailAccessService::PERM_RUN_SYNC));
                })
                ->action(function (MailSyncService $syncService): void {
                    @set_time_limit(180);
                    @ini_set('max_execution_time', '180');

                    $accounts = MailAccessService::scopeAccounts(
                        MailAccount::where('is_active', true)->whereNotNull('host')->where('host', '!=', ''),
                        auth()->user()
                    )->get();

                    if ($accounts->isEmpty()) {
                        Notification::make()
                            ->warning()
                            ->title(__('mail_inbox.messages.no_active_accounts'))
                            ->send();

                        return;
                    }

                    $totalImported = 0;
                    $totalFound = 0;
                    $totalFailedMessages = 0;
                    $connectionFailedAccounts = [];
                    $partialAccounts = [];

                    foreach ($accounts as $account) {
                        try {
                            $res = $syncService->syncAccount($account);
                            $totalFound += $res['found'] ?? 0;
                            $totalImported += $res['imported'] ?? 0;

                            $fresh = $account->fresh();
                            if ($fresh && $fresh->status === MailAccountStatus::CONNECTION_ERROR) {
                                $connectionFailedAccounts[] = $account->email;
                            } elseif (($res['failed'] ?? 0) > 0) {
                                $totalFailedMessages += (int) $res['failed'];
                                $partialAccounts[] = $account->email;
                            }
                        } catch (\Throwable) {
                            $connectionFailedAccounts[] = $account->email;
                        }
                    }

                    if (! empty($connectionFailedAccounts) || ! empty($partialAccounts)) {
                        $bodyParts = [];

                        if (! empty($connectionFailedAccounts)) {
                            $bodyParts[] = __('mail_inbox.messages.sync_partial_result', [
                                'imported' => $totalImported,
                                'failed' => implode(', ', $connectionFailedAccounts),
                            ]);
                        }

                        if (! empty($partialAccounts)) {
                            $bodyParts[] = __('mail_inbox.messages.sync_partial_messages_result', [
                                'imported' => $totalImported,
                                'failed_count' => $totalFailedMessages,
                                'accounts' => implode(', ', $partialAccounts),
                            ]);
                        }

                        Notification::make()
                            ->warning()
                            ->title(__('mail_inbox.messages.sync_completed_with_warning'))
                            ->body(implode("\n", $bodyParts))
                            ->send();
                    } else {
                        Notification::make()
                            ->success()
                            ->title(__('mail_inbox.messages.sync_completed_success'))
                            ->body(__('mail_inbox.messages.sync_full_result', [
                                'found' => $totalFound,
                                'imported' => $totalImported,
                            ]))
                            ->send();
                    }
                }),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'important';
    }

    public function getTabs(): array
    {
        $user = auth()->user();
        $baseQuery = fn () => MailAccessService::scopeRecords(MailMessage::query(), $user, 'emails');

        return [
            'important' => Tab::make(__('mail_inbox.tabs.important'))
                ->icon('heroicon-m-star')
                ->badgeColor('warning')
                ->badge($baseQuery()->where('is_important', true)->where('status', '!=', MailMessageStatus::REJECTED->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_important', true)->where('status', '!=', MailMessageStatus::REJECTED->value)),

            'under_review' => Tab::make(__('mail_inbox.tabs.under_review'))
                ->icon('heroicon-m-clock')
                ->badgeColor('info')
                ->badge($baseQuery()->where('status', MailMessageStatus::UNDER_REVIEW->value)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', MailMessageStatus::UNDER_REVIEW->value)),

            'spam_excluded' => Tab::make(__('mail_inbox.tabs.spam_excluded'))
                ->icon('heroicon-m-no-symbol')
                ->badgeColor('danger')
                ->badge($baseQuery()->where(fn (Builder $q) => $q->where('status', MailMessageStatus::REJECTED->value)->orWhere('category', MailCategory::SPAM->value)->orWhere('category', MailCategory::MARKETING->value))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where(fn (Builder $q) => $q->where('status', MailMessageStatus::REJECTED->value)->orWhere('category', MailCategory::SPAM->value)->orWhere('category', MailCategory::MARKETING->value))),

            'all' => Tab::make(__('mail_inbox.tabs.all'))
                ->icon('heroicon-m-inbox-stack')
                ->badge($baseQuery()->count()),
        ];
    }
}
