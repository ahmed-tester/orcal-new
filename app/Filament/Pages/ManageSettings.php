<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Cache;

class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $slug = 'settings';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.manage-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSuperAdminOrOwner() || $user->can('view_any_setting') || $user->can('manage_settings') || $user->can('update_setting'));
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.system_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.settings');
    }

    public function getTitle(): string|Htmlable
    {
        return __('settings.plural');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reset_database')
                ->label(__('settings.manage.database_reset.button_label'))
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn () => (auth()->user()?->isSuperAdmin() ?? false) || (auth()->user()?->can('reset_database') ?? false))
                ->requiresConfirmation()
                ->modalHeading(__('settings.manage.database_reset.modal_heading'))
                ->modalDescription(__('settings.manage.database_reset.modal_description'))
                ->modalSubmitActionLabel(__('settings.manage.database_reset.submit_label'))
                ->form([
                    Checkbox::make('confirm_reset')
                        ->label(__('settings.manage.database_reset.checkbox_label'))
                        ->required()
                        ->accepted(),
                ])
                ->action(function () {
                    try {
                        $result = \App\Services\DatabaseResetService::resetDatabase();

                        Notification::make()
                            ->title(__('settings.manage.database_reset.success_title'))
                            ->body(__('settings.manage.database_reset.success_body', [
                                'tables' => $result['truncated_tables_count'],
                                'admins' => $result['preserved_admins'],
                            ]))
                            ->success()
                            ->duration(10000)
                            ->send();

                        $this->redirect(static::getUrl());
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title(__('settings.manage.database_reset.failed_title'))
                            ->body($e->getMessage())
                            ->danger()
                            ->duration(12000)
                            ->send();
                    }
                }),
        ];
    }

    public function mount(): void
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $this->form->fill($settings);
    }

    public function form(Schema $schema): Schema
    {
        $user = auth()->user();
        $canEdit = $user && ($user->isSuperAdminOrOwner() || $user->can('manage_settings') || $user->can('update_setting'));

        return $schema
            ->components([
                Tabs::make('settings_tabs')
                    ->tabs([
                        // 1. General Settings Tab
                        Tab::make('general')
                            ->label(__('settings.groups.general'))
                            ->icon('heroicon-m-cog-6-tooth')
                            ->schema([
                                Section::make(__('settings.groups.general'))
                                    ->description(__('settings.manage.general.section_desc'))
                                    ->icon('heroicon-o-building-office')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('site_name')
                                            ->label(__('settings.manage.general.site_name'))
                                            ->helperText(__('settings.manage.general.site_name_helper'))
                                            ->required()
                                            ->columnSpan(2)
                                            ->disabled(!$canEdit),

                                        TextInput::make('company_phone')
                                            ->label(__('settings.manage.general.company_phone'))
                                            ->helperText(__('settings.manage.general.company_phone_helper'))
                                            ->tel()
                                            ->disabled(!$canEdit),

                                        TextInput::make('company_email')
                                            ->label(__('settings.manage.general.company_email'))
                                            ->helperText(__('settings.manage.general.company_email_helper'))
                                            ->email()
                                            ->disabled(!$canEdit),
                                    ]),
                            ]),

                        // 2. Alerts Tab
                        Tab::make('alerts')
                            ->label(__('settings.groups.alerts'))
                            ->icon('heroicon-m-bell-alert')
                            ->schema([
                                Section::make(__('settings.groups.alerts'))
                                    ->description(__('settings.manage.alerts.section_desc'))
                                    ->icon('heroicon-o-clock')
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('iqama_alert_first_days')
                                            ->label(__('settings.manage.alerts.iqama_alert_first_days'))
                                            ->helperText(__('settings.manage.alerts.iqama_alert_first_days_helper'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(90)
                                            ->disabled(!$canEdit),

                                        TextInput::make('iqama_alert_second_days')
                                            ->label(__('settings.manage.alerts.iqama_alert_second_days'))
                                            ->helperText(__('settings.manage.alerts.iqama_alert_second_days_helper'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(60)
                                            ->disabled(!$canEdit),

                                        TextInput::make('iqama_alert_third_days')
                                            ->label(__('settings.manage.alerts.iqama_alert_third_days'))
                                            ->helperText(__('settings.manage.alerts.iqama_alert_third_days_helper'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(30)
                                            ->disabled(!$canEdit),
                                    ]),
                            ]),

                        // 3. Uploads Tab
                        Tab::make('uploads')
                            ->label(__('settings.groups.uploads'))
                            ->icon('heroicon-m-document-arrow-up')
                            ->schema([
                                Section::make(__('settings.groups.uploads'))
                                    ->description(__('settings.manage.uploads.section_desc'))
                                    ->icon('heroicon-o-paper-clip')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('allowed_upload_extensions')
                                            ->label(__('settings.manage.uploads.allowed_upload_extensions'))
                                            ->helperText(__('settings.manage.uploads.allowed_upload_extensions_helper'))
                                            ->default('pdf,jpg,jpeg,png')
                                            ->required()
                                            ->disabled(!$canEdit),

                                        TextInput::make('max_upload_size_kb')
                                            ->label(__('settings.manage.uploads.max_upload_size_kb'))
                                            ->helperText(__('settings.manage.uploads.max_upload_size_kb_helper'))
                                            ->numeric()
                                            ->minValue(100)
                                            ->default(5120)
                                            ->required()
                                            ->disabled(!$canEdit),
                                    ]),
                            ]),

                        // 4. AI Tab
                        Tab::make('ai')
                            ->label(__('settings.groups.ai'))
                            ->icon('heroicon-m-sparkles')
                            ->schema([
                                Section::make(__('settings.groups.ai'))
                                    ->description(__('settings.manage.ai.section_desc'))
                                    ->icon('heroicon-o-cpu-chip')
                                    ->headerActions([
                                        Action::make('test_gemini_header')
                                            ->label(__('settings.manage.ai.test_header_btn'))
                                            ->icon('heroicon-m-bolt')
                                            ->color('success')
                                            ->action(fn ($get) => $this->testGeminiConnection($get)),
                                    ])
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('gemini_api_key')
                                            ->label(__('settings.manage.ai.gemini_api_key'))
                                            ->helperText(__('settings.manage.ai.gemini_api_key_helper'))
                                            ->password()
                                            ->revealable()
                                            ->live()
                                            ->disabled(!$canEdit),

                                        Select::make('gemini_model')
                                            ->label(__('settings.manage.ai.gemini_model'))
                                            ->helperText(__('settings.manage.ai.gemini_model_helper'))
                                            ->options([
                                                'gemini-3.5-flash' => __('settings.manage.ai.models.gemini_3_5_flash'),
                                                'gemini-3.6-flash' => __('settings.manage.ai.models.gemini_3_6_flash'),
                                                'gemini-3.7-flash' => __('settings.manage.ai.models.gemini_3_7_flash'),
                                                'gemini-3.8-flash' => __('settings.manage.ai.models.gemini_3_8_flash'),
                                                'gemini-3.5-flash-lite' => __('settings.manage.ai.models.gemini_3_5_flash_lite'),
                                            ])
                                            ->default('gemini-3.5-flash')
                                            ->required()
                                            ->live()
                                            ->disabled(!$canEdit),

                                        \Filament\Schemas\Components\Actions::make([
                                            Action::make('test_gemini_btn')
                                                ->label(__('settings.manage.ai.test_btn'))
                                                ->icon('heroicon-m-check-badge')
                                                ->color('success')
                                                ->action(fn ($get) => $this->testGeminiConnection($get)),
                                        ])->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->persistTab()
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function getFormActions(): array
    {
        $user = auth()->user();
        $canEdit = $user && ($user->isSuperAdminOrOwner() || $user->can('manage_settings') || $user->can('update_setting'));

        if (!$canEdit) {
            return [];
        }

        return [
            Action::make('save')
                ->label(__('settings.manage.save_changes'))
                ->icon('heroicon-m-check')
                ->color('primary')
                ->submit('saveSettings')
                ->keyBindings(['mod+s']),
        ];
    }

    public function saveSettings(): void
    {
        $formData = $this->form->getState();

        foreach ($formData as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );

            Cache::forget("setting_{$key}");
            Cache::forget("setting_is_active_{$key}");
        }

        Cache::forget('settings_all');

        Notification::make()
            ->title(__('settings.manage.save_success'))
            ->success()
            ->send();
    }

    /**
     * Test connection to Google Gemini AI API with currently selected key and model.
     */
    public function testGeminiConnection($get = null): void
    {
        $apiKey = is_callable($get) ? $get('gemini_api_key') : ($this->data['gemini_api_key'] ?? null);
        $model = is_callable($get) ? $get('gemini_model') : ($this->data['gemini_model'] ?? null);

        if (blank($apiKey)) {
            $apiKey = Setting::getGeminiApiKey();
        }

        if (blank($model)) {
            $model = Setting::getGeminiModel();
        }

        if (blank($apiKey)) {
            Notification::make()
                ->title(__('settings.manage.ai.no_api_key_title'))
                ->body(__('settings.manage.ai.no_api_key_body'))
                ->warning()
                ->send();
            return;
        }

        try {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
            $response = \Illuminate\Support\Facades\Http::timeout(15)->post($endpoint, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'Hello, respond with SUCCESS only.']
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                Notification::make()
                    ->title(__('settings.manage.ai.test_success_title'))
                    ->body(__('settings.manage.ai.test_success_body', ['model' => $model]))
                    ->success()
                    ->duration(8000)
                    ->send();
            } else {
                $rawError = $response->json('error.message') ?? ('HTTP ' . $response->status());
                $formattedError = $this->formatGeminiErrorMessage($rawError);

                Notification::make()
                    ->title(__('settings.manage.ai.test_failed_title'))
                    ->body(__('settings.manage.ai.test_failed_body', ['error' => $formattedError]))
                    ->danger()
                    ->duration(12000)
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title(__('settings.manage.ai.test_error_title'))
                ->body($e->getMessage())
                ->danger()
                ->duration(12000)
                ->send();
        }
    }

    /**
     * Translate Google API error messages to user-friendly Arabic/English messages.
     */
    protected function formatGeminiErrorMessage(string $rawError): string
    {
        if (str_contains($rawError, 'API key not valid') || str_contains($rawError, 'API_KEY_INVALID')) {
            return __('settings.manage.ai.errors.invalid_api_key');
        }

        if (str_contains($rawError, 'exceeded your current quota') || str_contains($rawError, 'RESOURCE_EXHAUSTED')) {
            return __('settings.manage.ai.errors.quota_exceeded');
        }

        if (str_contains($rawError, 'is no longer available') || str_contains($rawError, 'not found')) {
            return __('settings.manage.ai.errors.model_not_found');
        }

        if (str_contains($rawError, 'PERMISSION_DENIED')) {
            return __('settings.manage.ai.errors.permission_denied');
        }

        return $rawError;
    }
}

