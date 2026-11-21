<?php

namespace App\Filament\Resources\Mail\MailAccounts\Schemas;

use App\Services\CompanyFormService;
use App\Services\Mail\MailAccountService;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class MailAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('mail_inbox.account_form.basic_section'))
                ->description(__('mail_inbox.account_form.basic_description'))
                ->icon('heroicon-o-at-symbol')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    CompanyFormService::makeCompanySelect('company_id', __('mail_inbox.fields.company'), required: true)
                        ->live()
                        ->afterStateUpdated(fn (callable $set) => $set('employees', []))
                        ->disabled(fn ($record) => $record !== null && $record->messages()->exists()),
                    CompanyFormService::makeMultiEmployeeSelect(
                        name: 'employees',
                        label: __('mail_inbox.fields.employees'),
                        required: true,
                        relationshipName: 'employees',
                        companyFieldName: 'company_id',
                    )->saveRelationshipsUsing(static function (): void {}),
                    Hidden::make('provider_manually_selected')
                        ->default(false)
                        ->dehydrated(false),
                    Select::make('provider')
                        ->label(__('mail_inbox.fields.provider'))
                        ->options(fn (): array => collect(MailAccountService::providerOptions())
                            ->mapWithKeys(fn (string $provider): array => [$provider => __('mail_inbox.providers.'.$provider)])
                            ->all())
                        ->default(MailAccountService::PROVIDER_CUSTOM)
                        ->required()
                        ->live()
                        ->afterStateHydrated(function (Select $component, $record): void {
                            if ($record) {
                                $inferred = MailAccountService::inferProvider($record->email, $record->host);
                                $component->state(
                                    in_array($inferred, MailAccountService::providerOptions(), true)
                                        ? $inferred
                                        : MailAccountService::PROVIDER_CUSTOM
                                );
                            }
                        })
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                            $set('provider_manually_selected', true);
                            static::applyProviderDefaults($set, (string) $state, (string) $get('email'));
                        }),
                    TextInput::make('email')
                        ->label(__('mail_inbox.fields.email'))
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state, $record): void {
                            $previousProvider = $get('provider');
                            $inferred = MailAccountService::inferProvider($state);
                            $provider = ($record !== null || $get('provider_manually_selected'))
                                ? (string) $previousProvider
                                : (in_array($inferred, MailAccountService::providerOptions(), true)
                                    ? $inferred
                                    : MailAccountService::PROVIDER_CUSTOM);
                            $set('provider', $provider);

                            if ($provider === MailAccountService::PROVIDER_CUSTOM && $previousProvider === $provider) {
                                $defaults = MailAccountService::providerDefaults($provider, $state);
                                foreach (['host', 'username'] as $field) {
                                    if (blank($get($field))) {
                                        $set($field, $defaults[$field]);
                                    }
                                }

                                return;
                            }

                            static::applyProviderDefaults($set, $provider, $state);
                        }),
                    TextInput::make('name')
                        ->label(__('mail_inbox.fields.account_name'))
                        ->placeholder(__('mail_inbox.account_form.name_placeholder'))
                        ->required()
                        ->maxLength(255),
                    Toggle::make('is_active')
                        ->label(__('mail_inbox.account_form.enable_sync'))
                        ->default(true),
                ]),

            Section::make(fn (Get $get): string => __('mail_inbox.provider_setup.'.static::provider($get).'.title'))
                ->description(fn (Get $get): string => __('mail_inbox.provider_setup.'.static::provider($get).'.description'))
                ->icon(fn (Get $get): string => static::provider($get) === MailAccountService::PROVIDER_CUSTOM ? 'heroicon-o-server-stack' : 'heroicon-o-envelope')
                ->columns(fn (Get $get): int => static::provider($get) === MailAccountService::PROVIDER_CUSTOM ? 3 : 2)
                ->columnSpanFull()
                ->schema([
                    Placeholder::make('provider_instructions')
                        ->label(fn (Get $get): string => __('mail_inbox.provider_setup.'.static::provider($get).'.instructions'))
                        ->content(fn (Get $get) => view('filament.mail.account-provider-instructions', ['provider' => static::provider($get)]))
                        ->columnSpanFull(),
                    TextInput::make('host')
                        ->label(__('mail_inbox.fields.host'))
                        ->placeholder(fn (Get $get): string => (string) __('mail_inbox.providers.'.($get('provider') ?: 'custom').'_host_placeholder'))
                        ->maxLength(255)
                        ->visible(fn (Get $get): bool => static::provider($get) === MailAccountService::PROVIDER_CUSTOM)
                        ->dehydrated(fn (Get $get): bool => ($get('provider') ?: MailAccountService::PROVIDER_CUSTOM) === MailAccountService::PROVIDER_CUSTOM),
                    TextInput::make('port')
                        ->label(__('mail_inbox.fields.port'))
                        ->numeric()
                        ->default(993)
                        ->minValue(1)
                        ->maxValue(65535)
                        ->required()
                        ->visible(fn (Get $get): bool => static::provider($get) === MailAccountService::PROVIDER_CUSTOM)
                        ->dehydrated(fn (Get $get): bool => ($get('provider') ?: MailAccountService::PROVIDER_CUSTOM) === MailAccountService::PROVIDER_CUSTOM),
                    Select::make('encryption')
                        ->label(__('mail_inbox.fields.encryption'))
                        ->options([
                            'ssl' => __('mail_inbox.encryptions.ssl'),
                            'tls' => __('mail_inbox.encryptions.tls'),
                        ])
                        ->default('ssl')
                        ->required()
                        ->visible(fn (Get $get): bool => static::provider($get) === MailAccountService::PROVIDER_CUSTOM)
                        ->dehydrated(fn (Get $get): bool => ($get('provider') ?: MailAccountService::PROVIDER_CUSTOM) === MailAccountService::PROVIDER_CUSTOM),
                    TextInput::make('username')
                        ->label(__('mail_inbox.fields.username'))
                        ->placeholder(fn (Get $get): string => (string) __('mail_inbox.providers.'.($get('provider') ?: 'custom').'_username_placeholder'))
                        ->maxLength(255)
                        ->visible(fn (Get $get): bool => static::provider($get) === MailAccountService::PROVIDER_CUSTOM)
                        ->dehydrated(fn (Get $get): bool => ($get('provider') ?: MailAccountService::PROVIDER_CUSTOM) === MailAccountService::PROVIDER_CUSTOM),
                    TextInput::make('password')
                        ->label(fn (Get $get): string => __('mail_inbox.provider_setup.'.static::provider($get).'.password_label'))
                        ->password()
                        ->revealable()
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn (?string $state): bool => filled($state))
                        ->suffixAction(
                            Action::make('open_google_app_passwords')
                                ->icon('heroicon-m-arrow-top-right-on-square')
                                ->label(__('mail_inbox.provider_setup.gmail.link_label'))
                                ->url('https://myaccount.google.com/apppasswords', shouldOpenInNewTab: true)
                                ->visible(fn (Get $get): bool => static::provider($get) === MailAccountService::PROVIDER_GMAIL)
                        )
                        ->helperText(fn (Get $get): string => (string) __('mail_inbox.providers.'.($get('provider') ?: 'custom').'_password_hint')),
                    Select::make('sync_interval_minutes')
                        ->label(__('mail_inbox.fields.sync_interval'))
                        ->options(fn (): array => collect([1, 5, 15, 30, 60])
                            ->mapWithKeys(fn (int $minutes): array => [$minutes => __('mail_inbox.sync_intervals.'.$minutes)])
                            ->all())
                        ->default(1)
                        ->required(),
                ]),
        ]);
    }

    protected static function applyProviderDefaults(Set $set, string $provider, string $email): void
    {
        $defaults = MailAccountService::providerDefaults($provider, $email);
        $set('host', $defaults['host']);
        $set('port', $defaults['port']);
        $set('encryption', $defaults['encryption']);

        if (filled($defaults['username'])) {
            $set('username', $defaults['username']);
        }
    }

    protected static function provider(Get $get): string
    {
        $provider = (string) $get('provider');

        return in_array($provider, MailAccountService::providerOptions(), true) ? $provider : MailAccountService::PROVIDER_CUSTOM;
    }
}
