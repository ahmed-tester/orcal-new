<?php

namespace App\Filament\Resources\Mail\MailAccounts\Pages;

use App\Filament\Resources\Mail\MailAccounts\MailAccountResource;
use App\Models\Mail\MailAccount;
use App\Services\Mail\MailAccountService;
use App\Services\Mail\MicrosoftMailOAuthService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class EditMailAccount extends EditRecord
{
    protected static string $resource = MailAccountResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $employeeIds = (array) ($this->data['employees'] ?? []);

        return app(MailAccountService::class)->saveAccount(
            $record,
            $data,
            $employeeIds,
            auth()->user()
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('connect_microsoft')
                ->label(fn (): string => $this->record->hasOAuthTokens()
                    ? __('mail_inbox.actions.reconnect_microsoft')
                    : __('mail_inbox.actions.connect_microsoft'))
                ->icon('heroicon-o-arrow-right-end-on-rectangle')
                ->color('primary')
                ->visible(fn (): bool => $this->record instanceof MailAccount
                    && $this->record->isOutlookOAuth()
                    && Gate::allows('update', $this->record))
                ->disabled(fn (): bool => ! app(MicrosoftMailOAuthService::class)->isConfigured())
                ->action(function (MicrosoftMailOAuthService $oauthService): void {
                    $auth = $oauthService->beginAuthorization(
                        $this->record,
                        auth()->user(),
                        MailAccountResource::getUrl('edit', ['record' => $this->record])
                    );

                    $this->redirect($auth['url']);
                }),
            Action::make('disconnect_microsoft')
                ->label(__('mail_inbox.actions.disconnect_microsoft'))
                ->icon('heroicon-o-link-slash')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record instanceof MailAccount
                    && $this->record->isOutlookOAuth()
                    && $this->record->hasOAuthTokens()
                    && Gate::allows('update', $this->record))
                ->action(function (MicrosoftMailOAuthService $oauthService): void {
                    $oauthService->disconnect($this->record, auth()->user());
                    $this->record->refresh();
                    $this->fillForm();

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
                ->visible(fn (): bool => $this->record instanceof MailAccount
                    && ! $this->record->isOutlookOAuth()
                    && MailAccountService::inferProvider($this->record->email, $this->record->host) === MailAccountService::PROVIDER_OUTLOOK
                    && Gate::allows('update', $this->record))
                ->action(function (MailAccountService $accountService): void {
                    $this->record = $accountService->convertLegacyOutlookToOAuth($this->record, auth()->user());
                    $this->fillForm();

                    Notification::make()
                        ->title(__('mail_inbox.oauth.converted_to_oauth'))
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
