<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Services\PhoneService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('organization.companies.sections.basic_info'))
                    ->description(__('organization.companies.sections.basic_info_desc'))
                    ->icon('heroicon-o-building-office')
                    ->columns(3)
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                FileUpload::make('logo')
                                    ->label(__('organization.companies.fields.logo'))
                                    ->image()
                                    ->directory('companies/logos')
                                    ->disk('public')
                                    ->imageEditor()
                                    ->imagePreviewHeight('160')
                                    ->openable()
                                    ->downloadable()
                                    ->maxSize(\App\Models\Setting::getMaxUploadSizeKb()),

                                FileUpload::make('metadata.stamp')
                                    ->label(__('organization.companies.fields.stamp'))
                                    ->image()
                                    ->directory('companies/stamps')
                                    ->disk('public')
                                    ->imageEditor()
                                    ->imagePreviewHeight('160')
                                    ->openable()
                                    ->downloadable()
                                    ->maxSize(\App\Models\Setting::getMaxUploadSizeKb()),
                            ]),

                        TextInput::make('trade_name')
                            ->label(__('organization.companies.fields.trade_name'))
                            ->placeholder(__('organization.companies.fields.main_trade_name_placeholder') ?? 'مثال: شركة أوركال')
                            ->required(),

                        TextInput::make('trade_name_en')
                            ->label(__('organization.companies.fields.trade_name_en'))
                            ->placeholder('e.g. Nawas General Contracting Est.'),

                        TextInput::make('company_number')
                            ->label(__('organization.companies.fields.company_number'))
                            ->unique(\App\Models\Company::class, 'company_number', ignoreRecord: true)
                            ->nullable(),

                        Select::make('status')
                            ->label(__('organization.companies.fields.status'))
                            ->options(__('organization.statuses'))
                            ->default('active')
                            ->required(),

                        PhoneService::makeFormField('phone', __('organization.companies.fields.phone')),

                        TextInput::make('email')
                            ->label(__('organization.companies.fields.email'))
                            ->email(),

                        Textarea::make('address')
                            ->label(__('organization.companies.fields.address'))
                            ->placeholder('مثال: الدمام - حي مدينة العمال - المملكة العربية السعودية')
                            ->rows(2)
                            ->columnSpan(2),

                        TextInput::make('postal_code')
                            ->label(__('organization.companies.fields.postal_code'))
                            ->placeholder('مثال: 32253')
                            ->columnSpan(1),

                        Textarea::make('address_en')
                            ->label(__('organization.companies.fields.address_en'))
                            ->placeholder('e.g. Dammam - Workers City District - Kingdom of Saudi Arabia')
                            ->rows(2)
                            ->columnSpanFull(),

                        Textarea::make('additional_info')
                            ->label(__('organization.companies.fields.additional_info'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('organization.companies.sections.licenses'))
                    ->description(__('organization.companies.sections.licenses_desc'))
                    ->icon('heroicon-o-document-text')
                    ->columns(3)
                    ->schema([
                        // 1. السجل التجاري
                        TextInput::make('commercial_registration_number')
                            ->label(__('organization.companies.fields.cr_number'))
                            ->unique(\App\Models\Company::class, 'commercial_registration_number', ignoreRecord: true)
                            ->nullable(),

                        DatePicker::make('commercial_registration_issue_date')
                            ->label(__('organization.companies.fields.cr_issue_date'))
                            ->native(false),

                        DatePicker::make('commercial_registration_expiry')
                            ->label(__('organization.companies.fields.cr_expiry_date'))
                            ->native(false),

                        // 2. رخصة البلدية
                        TextInput::make('municipality_license_number')
                            ->label(__('organization.companies.fields.municipality_number')),

                        DatePicker::make('municipality_license_issue_date')
                            ->label(__('organization.companies.fields.municipality_issue_date'))
                            ->native(false),

                        DatePicker::make('municipality_license_expiry')
                            ->label(__('organization.companies.fields.municipality_expiry_date'))
                            ->native(false),

                        // 3. تصريح الدفاع المدني
                        TextInput::make('civil_defense_license_number')
                            ->label(__('organization.companies.fields.civil_defense_number')),

                        DatePicker::make('civil_defense_license_issue_date')
                            ->label(__('organization.companies.fields.civil_defense_issue_date'))
                            ->native(false),

                        DatePicker::make('civil_defense_license_expiry')
                            ->label(__('organization.companies.fields.civil_defense_expiry_date'))
                            ->native(false),
                    ]),

                Section::make(__('organization.companies.sections.gov_numbers'))
                    ->description(__('organization.companies.sections.gov_numbers_desc'))
                    ->icon('heroicon-o-identification')
                    ->columns(3)
                    ->schema([
                        TextInput::make('interior_ministry_number')
                            ->label(__('organization.companies.fields.interior_ministry_number')),

                        TextInput::make('labor_ministry_number')
                            ->label(__('organization.companies.fields.labor_ministry_number')),

                        TextInput::make('social_insurance_number')
                            ->label(__('organization.companies.fields.social_insurance_number')),

                        TextInput::make('zakat_number')
                            ->label(__('organization.companies.fields.zakat_number')),

                        TextInput::make('chamber_of_commerce_number')
                            ->label(__('organization.companies.fields.chamber_of_commerce_number')),
                    ]),

                Section::make(__('organization.branches.plural'))
                    ->description(__('organization.branches.sections.info'))
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Repeater::make('branches')
                            ->relationship('branches')
                            ->label(__('organization.branches.plural'))
                            ->addActionLabel(__('organization.branches.actions.add_branch') ?? 'إضافة فرع جديد')
                            ->columns(3)
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('organization.branches.fields.name'))
                                    ->required(),

                                PhoneService::makeFormField('phone', __('organization.branches.fields.phone')),

                                TextInput::make('email')
                                    ->label(__('organization.branches.fields.email'))
                                    ->email(),

                                Select::make('status')
                                    ->label(__('organization.branches.fields.status'))
                                    ->options(__('organization.statuses'))
                                    ->default('active')
                                    ->required(),

                                Textarea::make('address')
                                    ->label(__('organization.branches.fields.address'))
                                    ->rows(2)
                                    ->columnSpanFull(),

                                Section::make(__('organization.branches.sections.attendance_settings'))
                                    ->description(__('organization.branches.sections.attendance_settings_desc'))
                                    ->icon('heroicon-o-clock')
                                    ->collapsible()
                                    ->columns(3)
                                    ->columnSpanFull()
                                    ->schema([
                                        TimePicker::make('attendance_settings.check_in_time')
                                            ->label(__('organization.branches.fields.check_in_time'))
                                            ->default('08:00')
                                            ->seconds(false)
                                            ->format('H:i'),

                                        TimePicker::make('attendance_settings.check_out_time')
                                            ->label(__('organization.branches.fields.check_out_time'))
                                            ->default('16:00')
                                            ->seconds(false)
                                            ->format('H:i'),

                                        TextInput::make('attendance_settings.late_grace_minutes')
                                            ->label(__('organization.branches.fields.late_grace_minutes'))
                                            ->helperText(__('organization.branches.fields.late_grace_minutes_helper'))
                                            ->numeric()
                                            ->default(15)
                                            ->minValue(0)
                                            ->maxValue(120),

                                        Toggle::make('attendance_settings.late_deduction_enabled')
                                            ->label(__('organization.branches.fields.late_deduction_enabled'))
                                            ->helperText(__('organization.branches.fields.late_deduction_enabled_desc'))
                                            ->default(false)
                                            ->live()
                                            ->columnSpanFull(),

                                        Grid::make(2)
                                            ->visible(fn (callable $get) => (bool) $get('attendance_settings.late_deduction_enabled'))
                                            ->columnSpanFull()
                                            ->schema([
                                                TextInput::make('attendance_settings.late_quarter_day_minutes')
                                                    ->label(__('organization.branches.fields.late_quarter_day_minutes'))
                                                    ->helperText(__('organization.branches.fields.late_quarter_day_minutes_helper'))
                                                    ->numeric()
                                                    ->default(30)
                                                    ->minValue(1)
                                                    ->maxValue(240),

                                                TextInput::make('attendance_settings.late_half_day_minutes')
                                                    ->label(__('organization.branches.fields.late_half_day_minutes'))
                                                    ->helperText(__('organization.branches.fields.late_half_day_minutes_helper'))
                                                    ->numeric()
                                                    ->default(60)
                                                    ->minValue(1)
                                                    ->maxValue(480),
                                            ]),

                                        Toggle::make('attendance_settings.absence_penalty_enabled')
                                            ->label(__('organization.branches.fields.absence_penalty_enabled'))
                                            ->helperText(__('organization.branches.fields.absence_penalty_enabled_desc'))
                                            ->default(false)
                                            ->live()
                                            ->columnSpanFull(),

                                        Grid::make(1)
                                            ->visible(fn (callable $get) => (bool) $get('attendance_settings.absence_penalty_enabled'))
                                            ->columnSpanFull()
                                            ->schema([
                                                TextInput::make('attendance_settings.absence_deduction_multiplier')
                                                    ->label(__('organization.branches.fields.absence_deduction_multiplier'))
                                                    ->helperText(__('organization.branches.fields.absence_deduction_multiplier_helper'))
                                                    ->numeric()
                                                    ->step(0.5)
                                                    ->default(1.0)
                                                    ->minValue(1.0)
                                                    ->maxValue(10.0),
                                            ]),
                                    ]),
                            ])
                            ->collapsible()
                            ->defaultItems(0)
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('organization.companies.sections.attachments'))
                    ->description(__('organization.companies.sections.attachments_desc'))
                    ->icon('heroicon-o-paper-clip')
                    ->schema([
                        Repeater::make('attachments')
                            ->relationship('attachments')
                            ->label(__('organization.companies.sections.attachments'))
                            ->addActionLabel(__('organization.companies.attachment_fields.add_attachment'))
                            ->columns(3)
                            ->schema([
                                Select::make('attachment_type')
                                    ->label(__('organization.companies.attachment_fields.type'))
                                    ->options(__('organization.companies.attachment_types'))
                                    ->default('commercial_registration')
                                    ->required(),

                                TextInput::make('title')
                                    ->label(__('organization.companies.attachment_fields.title'))
                                    ->required(),

                                TextInput::make('document_number')
                                    ->label(__('organization.companies.attachment_fields.doc_number')),

                                DatePicker::make('start_date')
                                    ->label(__('organization.companies.attachment_fields.start_date'))
                                    ->native(false),

                                DatePicker::make('end_date')
                                    ->label(__('organization.companies.attachment_fields.end_date'))
                                    ->native(false),

                                FileUpload::make('file_path')
                                    ->label(__('organization.companies.attachment_fields.file'))
                                    ->directory('companies/attachments')
                                    ->acceptedFileTypes(\App\Models\Setting::getAllowedUploadMimeTypes())
                                    ->maxSize(\App\Models\Setting::getMaxUploadSizeKb())
                                    ->openable()
                                    ->downloadable(),

                                Textarea::make('notes')
                                    ->label(__('organization.companies.attachment_fields.notes'))
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => ($state['title'] ?? null) ? ($state['title'] . (isset($state['attachment_type']) ? ' (' . (__('organization.companies.attachment_types.' . $state['attachment_type']) ?: $state['attachment_type']) . ')' : '')) : null)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
