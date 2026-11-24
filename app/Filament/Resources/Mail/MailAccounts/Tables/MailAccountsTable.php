<?php

namespace App\Filament\Resources\Mail\MailAccounts\Tables;

use App\Enums\Mail\MailAccountStatus;
use App\Filament\Resources\Mail\MailAccounts\MailAccountResource;
use App\Models\Mail\MailAccount;
use App\Services\Mail\MailAccountService;
use App\Services\Mail\MailSyncService;
use App\Services\Mail\MicrosoftMailOAuthService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class MailAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('company.trade_name')
                    ->label(__('mail_inbox.fields.company'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('mail_inbox.fields.account_name'))
                    ->weight('semibold')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('mail_inbox.fields.email'))
                    ->icon('heroicon-m-envelope')
                    ->copyable()
                    ->copyMessage(__('mail_inbox.messages.email_copied'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('mail_inbox.fields.account_type'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'gmail_oauth' => __('mail_inbox.account_types.gmail_oauth'),
                        'outlook_oauth' => __('mail_inbox.account_types.outlook_oauth'),
                        default => __('mail_inbox.account_types.imap'),
                    }),
                TextColumn::make('employees.name')
                    ->label(__('mail_inbox.fields.employees'))
                    ->badge()
                    ->separator(', ')
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->placeholder(__('mail_inbox.fields.no_employees')),
                TextColumn::make('status')
                    ->label(__('mail_inbox.fields.status'))
                    ->badge()
                    ->description(fn (MailAccount $record): ?string => MicrosoftMailOAuthService::isVerifying((int) $record->id)
                        ? __('mail_inbox.oauth.verifying_syncing')
                        : null),
                IconColumn::make('is_active')
                    ->label(__('mail_inbox.fields.is_active'))
                    ->boolean(),
                TextColumn::make('last_sync_at')
                    ->label(__('mail_inbox.fields.last_sync_at'))
                    ->since()
                    ->placeholder(__('mail_inbox.fields.not_synced_yet'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('mail_inbox.fields.status_filter'))
                    ->options(MailAccountStatus::class),
            ])
            ->recordActions([
                Action::make('connect_microsoft')
                    ->label(fn (MailAccount $record): string => $record->hasOAuthTokens()
                        ? __('mail_inbox.actions.reconnect_microsoft')
                        : __('mail_inbox.actions.connect_microsoft'))
                    ->icon('heroicon-o-arrow-right-end-on-rectangle')
                    ->color('primary')
                    ->visible(fn (MailAccount $record): bool => $record->isOutlookOAuth() && Gate::allows('update', $record))
                    ->disabled(fn (): bool => ! app(MicrosoftMailOAuthService::class)->isConfigured())
                    ->action(function (MailAccount $record, MicrosoftMailOAuthService $oauthService, $livewire): void {
                        $auth = $oauthService->beginAuthorization(
                            $record,
                            auth()->user(),
                            MailAccountResource::getUrl('index')
                        );

                        $livewire->redirect($auth['url']);
                    }),
                Action::make('disconnect_microsoft')
                    ->label(__('mail_inbox.actions.disconnect_microsoft'))
                    ->icon('heroicon-o-link-slash')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (MailAccount $record): bool => $record->isOutlookOAuth()
                        && $record->hasOAuthTokens()
                        && Gate::allows('update', $record))
                    ->action(function (MailAccount $record, MicrosoftMailOAuthService $oauthService): void {
                        $oauthService->disconnect($record, auth()->user());

                        Notification::make()
                            ->title(__('mail_inbox.oauth.disconnected'))
                            ->success()
                            ->send();
                    }),
                Action::make('convert_to_oauth')
                    ->label(__('mail_inbox.actions.convert_to_oauth'))
                    ->icon('heroicon-o-arrow-path-rounded-square')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (MailAccount $record): bool => ! $record->isOutlookOAuth()
                        && MailAccountService::inferProvider($record->email, $record->host) === MailAccountService::PROVIDER_OUTLOOK
                        && Gate::allows('update', $record))
                    ->action(function (MailAccount $record, MailAccountService $accountService): void {
                        $accountService->convertLegacyOutlookToOAuth($record, auth()->user());

                        Notification::make()
                            ->title(__('mail_inbox.oauth.converted_to_oauth'))
                            ->success()
                            ->send();
                    }),
                Action::make('test_connection')
                    ->label(__('mail_inbox.actions.test_connection'))
                    ->icon('heroicon-o-signal')
                    ->visible(fn (MailAccount $record): bool => Gate::allows('update', $record)
                        && (! $record->isOAuth() || $record->hasOAuthTokens()))
                    ->action(function (MailAccount $record, MailSyncService $syncService): void {
                        Gate::authorize('update', $record);
                        $result = $syncService->testConnection($record);
                        Notification::make()
                            ->title($result['message'])
                            ->{$result['success'] ? 'success' : 'danger'}()
                            ->send();
                    }),
                Action::make('sync_now')
                    ->label(__('mail_inbox.actions.sync_now'))
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (MailAccount $record): bool => Gate::allows('sync', $record)
                        && (! $record->isOAuth() || $record->hasOAuthTokens()))
                    ->action(function (MailAccount $record, MailSyncService $syncService): void {
                        Gate::authorize('sync', $record);
                        $result = $syncService->syncAccount($record);
                        $record->refresh();

                        if ($record->status === MailAccountStatus::CONNECTION_ERROR) {
                            Notification::make()
                                ->title(__('mail_inbox.messages.sync_failed'))
                                ->body($record->error_message ?: __('mail_inbox.sync.failed_after_retries'))
                                ->danger()
                                ->send();

                            return;
                        }

                        if (($result['failed'] ?? 0) > 0) {
                            Notification::make()
                                ->title(__('mail_inbox.messages.sync_completed_with_warning'))
                                ->body(__('mail_inbox.messages.sync_partial_account', [
                                    'imported' => $result['imported'] ?? 0,
                                    'failed' => $result['failed'] ?? 0,
                                ]))
                                ->warning()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title(__('mail_inbox.messages.sync_completed', $result))
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (MailAccount $record): bool => Gate::allows('delete', $record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => Gate::allows('deleteAny', MailAccount::class)),
                ]),
            ]);
    }
}
