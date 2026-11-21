<?php

namespace App\Filament\Schemas;

use App\Services\Mail\EmailClassifierService;
use App\Services\Mail\MailAccessService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class MailSettingsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('mail_inbox.settings.ai_section'))
                ->description(__('mail_inbox.settings.ai_description'))
                ->icon('heroicon-o-sparkles')
                ->columns(2)
                ->schema([
                    TextInput::make('mail_gemini_model')
                        ->label(__('mail_inbox.settings.mail_gemini_model'))
                        ->required()
                        ->helperText(__('mail_inbox.settings.shared_key')),
                    TextInput::make('mail_gemini_daily_limit')
                        ->label(__('mail_inbox.settings.mail_gemini_daily_limit'))
                        ->numeric()
                        ->minValue(0)
                        ->required()
                        ->helperText(__('mail_inbox.settings.daily_limit_hint')),
                    Action::make('test_mail_gemini')
                        ->label(__('mail_inbox.settings.test_gemini'))
                        ->icon('heroicon-m-beaker')
                        ->color('gray')
                        ->action(function (Get $get): void {
                            abort_unless(auth()->user() && MailAccessService::canAdministerAllMail(auth()->user()), 403);
                            $result = app(EmailClassifierService::class)->testConnection((string) $get('mail_gemini_model'));
                            Notification::make()
                                ->title($result['message'])
                                ->{$result['success'] ? 'success' : 'danger'}()
                                ->send();
                        }),
                ]),
            Section::make(__('mail_inbox.settings.sync_section'))
                ->description(__('mail_inbox.settings.sync_description'))
                ->icon('heroicon-o-arrow-path')
                ->columns(2)
                ->schema([
                    TextInput::make('mail_escalation_hours')
                        ->label(__('mail_inbox.settings.mail_escalation_hours'))
                        ->numeric()
                        ->minValue(1)
                        ->required()
                        ->helperText(__('mail_inbox.settings.escalation_hint')),
                ]),
            Section::make(__('mail_inbox.settings.notifications_section'))
                ->description(__('mail_inbox.settings.notifications_description'))
                ->icon('heroicon-o-bell')
                ->columns(2)
                ->schema([
                    Toggle::make('mail_enable_email_notifications')
                        ->label(__('mail_inbox.settings.mail_enable_email_notifications'))
                        ->live()
                        ->columnSpanFull(),
                    TextInput::make('mail_notification_email')
                        ->label(__('mail_inbox.settings.mail_notification_email'))
                        ->email()
                        ->visible(fn (Get $get): bool => (bool) $get('mail_enable_email_notifications'))
                        ->required(fn (Get $get): bool => (bool) $get('mail_enable_email_notifications'))
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
