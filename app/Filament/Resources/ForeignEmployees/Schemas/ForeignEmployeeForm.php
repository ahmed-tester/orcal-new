<?php

namespace App\Filament\Resources\ForeignEmployees\Schemas;

use App\Services\EmployeeAgeService;
use App\Services\PhoneService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ForeignEmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('employees.sections.personal_info'))
                    ->description(__('employees.sections.personal_info_foreign_desc'))
                    ->icon('heroicon-o-user')
                    ->columns(3)
                    ->schema([
                        TextInput::make('user_name')
                            ->label(__('employees.fields.name_foreign'))
                            ->required(),

                        TextInput::make('user_email')
                            ->label(__('employees.fields.email'))
                            ->email()
                            ->required(),

                        PhoneService::makeFormField('user_phone', __('employees.fields.phone')),

                        Select::make('nationality')
                            ->label(__('employees.fields.nationality'))
                            ->options(__('employees.nationalities'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        TextInput::make('employee_number')
                            ->label(__('employees.fields.employee_number'))
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record !== null),

                        Select::make('gender')
                            ->label(__('employees.fields.gender'))
                            ->options(__('employees.gender_options'))
                            ->default('male'),

                        Select::make('marital_status')
                            ->label(__('employees.fields.marital_status'))
                            ->options(__('employees.marital_options')),

                        ...EmployeeAgeService::makeFormFields(),

                        TextInput::make('city')
                            ->label(__('employees.fields.city')),

                        Textarea::make('address')
                            ->label(__('employees.fields.address_foreign'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('employees.sections.legal_info'))
                    ->description(__('employees.sections.legal_info_desc'))
                    ->icon('heroicon-o-identification')
                    ->columns(3)
                    ->schema([
                        TextInput::make('iqama_number')
                            ->label(__('employees.fields.iqama_number'))
                            ->length(10)
                            ->required(),

                        DatePicker::make('iqama_expiry')
                            ->label(__('employees.fields.iqama_expiry'))
                            ->native(false)
                            ->required(),

                        TextInput::make('passport_number')
                            ->label(__('employees.fields.passport_number')),

                        DatePicker::make('passport_expiry')
                            ->label(__('employees.fields.passport_expiry'))
                            ->native(false),

                        DatePicker::make('insurance_expiry')
                            ->label(__('employees.fields.insurance_expiry'))
                            ->native(false),

                        TextInput::make('work_permit_number')
                            ->label(__('employees.fields.work_permit_number')),

                        DatePicker::make('work_permit_expiry')
                            ->label(__('employees.fields.work_permit_expiry'))
                            ->native(false),

                        // TextInput::make('sponsor_name')
                        //     ->label(__('employees.fields.sponsor_name')),

                        DatePicker::make('last_leave_return_date')
                            ->label(__('employees.fields.last_leave_return_date'))
                            ->native(false),
                    ]),

                Section::make(__('guarantors.section_title'))
                    ->description(__('guarantors.section_desc'))
                    ->icon('heroicon-o-user-group')
                    ->columns(3)
                    ->schema([
                        Radio::make('guarantor_option')
                            ->label(__('guarantors.fields.type'))
                            ->options([
                                'none' => __('guarantors.types.none'),
                                'internal' => __('guarantors.types.internal'),
                                'external' => __('guarantors.types.external'),
                            ])
                            ->default('none')
                            ->inline()
                            ->live()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Select::make('guarantor_company_id')
                            ->label(__('guarantors.fields.guarantor_company'))
                            ->placeholder(__('guarantors.placeholders.guarantor_company'))
                            ->options(function () {
                                $authorized = \App\Services\CompanyFormService::getAuthorizedCompanyIds();
                                $query = \App\Models\Company::query();
                                if (!empty($authorized)) {
                                    $query->whereIn('id', $authorized);
                                }
                                return $query->orderBy('trade_name')->pluck('trade_name', 'id')->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(fn ($get) => $get('guarantor_option') === 'internal')
                            ->afterStateUpdated(function ($state, $set) {
                                $set('guarantor_employee_id', null);
                                $set('guarantor_name', null);
                                $set('guarantor_national_id', null);
                                $set('guarantor_phone', null);
                                $set('guarantor_nationality', null);
                                $set('guarantor_job_title', null);
                                $set('guarantor_address', null);
                            })
                            ->dehydrated(false)
                            ->columnSpan(['default' => 3, 'lg' => 1]),

                        Select::make('guarantor_employee_id')
                            ->label(__('guarantors.fields.employee_id'))
                            ->placeholder(__('guarantors.placeholders.employee_select'))
                            ->options(function ($get, $record) {
                                $guarantorCompanyId = $get('guarantor_company_id');
                                $query = \App\Models\Employee::query()
                                    ->with(['user', 'details', 'company'])
                                    ->where('status', 'active');

                                if ($record && $record->id) {
                                    $query->where('id', '!=', $record->id);
                                }

                                if ($guarantorCompanyId) {
                                    $query->where('company_id', $guarantorCompanyId);
                                } else {
                                    $user = auth()->user();
                                    if ($user && ! $user->isSuperAdmin()) {
                                        $authorized = \App\Services\CompanyFormService::getAuthorizedCompanyIds($user);
                                        if (!empty($authorized)) {
                                            $query->whereIn('company_id', $authorized);
                                        }
                                    }
                                }

                                return $query->get()->mapWithKeys(function ($emp) use ($guarantorCompanyId) {
                                    $name = $emp->user?->name ?? $emp->employee_number ?? "#{$emp->id}";
                                    $idNum = $emp->details?->national_id ?? $emp->details?->iqama_number;
                                    $companyName = (! $guarantorCompanyId && $emp->company) ? " [{$emp->company->trade_name}]" : '';
                                    $label = $name . ($idNum ? " ({$idNum})" : '') . $companyName;
                                    return [$emp->id => $label];
                                });
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(fn ($get) => $get('guarantor_option') === 'internal')
                            ->afterStateUpdated(function ($state, $set, $get) {
                                if (! $state) {
                                    $set('guarantor_name', null);
                                    $set('guarantor_national_id', null);
                                    $set('guarantor_phone', null);
                                    $set('guarantor_nationality', null);
                                    $set('guarantor_job_title', null);
                                    $set('guarantor_address', null);
                                    return;
                                }
                                $emp = \App\Models\Employee::with(['user', 'details', 'jobTitle', 'company'])->find($state);
                                if ($emp) {
                                    if ($emp->company_id && ! $get('guarantor_company_id')) {
                                        $set('guarantor_company_id', $emp->company_id);
                                    }
                                    $set('guarantor_name', $emp->user?->name);
                                    $set('guarantor_national_id', $emp->details?->national_id ?? $emp->details?->iqama_number);
                                    $set('guarantor_phone', $emp->user?->phone);
                                    $set('guarantor_nationality', $emp->details?->nationality ?? 'saudi');
                                    $set('guarantor_job_title', $emp->jobTitle?->name);
                                    $set('guarantor_address', $emp->details?->address);
                                }
                            })
                            ->dehydrated(false)
                            ->columnSpan(['default' => 3, 'lg' => 2]),

                        TextInput::make('guarantor_name')
                            ->label(__('guarantors.fields.name'))
                            ->placeholder(__('guarantors.placeholders.name'))
                            ->visible(fn ($get) => in_array($get('guarantor_option'), ['internal', 'external']))
                            ->required(fn ($get) => in_array($get('guarantor_option'), ['internal', 'external']))
                            ->dehydrated(false),

                        TextInput::make('guarantor_national_id')
                            ->label(__('guarantors.fields.national_id'))
                            ->placeholder(__('guarantors.placeholders.national_id'))
                            ->visible(fn ($get) => in_array($get('guarantor_option'), ['internal', 'external']))
                            ->dehydrated(false),

                        PhoneService::makeFormField('guarantor_phone', __('guarantors.fields.phone'))
                            ->visible(fn ($get) => in_array($get('guarantor_option'), ['internal', 'external']))
                            ->dehydrated(false),

                        Select::make('guarantor_nationality')
                            ->label(__('guarantors.fields.nationality'))
                            ->options(__('employees.nationalities'))
                            ->searchable()
                            ->preload()
                            ->default('saudi')
                            ->visible(fn ($get) => in_array($get('guarantor_option'), ['internal', 'external']))
                            ->dehydrated(false),

                        TextInput::make('guarantor_job_title')
                            ->label(__('guarantors.fields.job_title'))
                            ->placeholder(__('guarantors.placeholders.job_title'))
                            ->visible(fn ($get) => in_array($get('guarantor_option'), ['internal', 'external']))
                            ->dehydrated(false),

                        TextInput::make('guarantor_address')
                            ->label(__('guarantors.fields.address'))
                            ->placeholder(__('guarantors.placeholders.address'))
                            ->visible(fn ($get) => in_array($get('guarantor_option'), ['internal', 'external']))
                            ->dehydrated(false),

                        Textarea::make('guarantor_notes')
                            ->label(__('guarantors.fields.notes'))
                            ->placeholder(__('guarantors.placeholders.notes'))
                            ->rows(2)
                            ->visible(fn ($get) => in_array($get('guarantor_option'), ['internal', 'external']))
                            ->dehydrated(false)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('employees.sections.job_info'))
                    ->description(__('employees.sections.job_info_desc'))
                    ->icon('heroicon-o-briefcase')
                    ->columns(3)
                    ->schema([
                        \App\Services\CompanyFormService::makeCompanySelect(),
                        \App\Services\CompanyFormService::makeBranchSelect(),
                        \App\Services\CompanyFormService::makeDepartmentSelect(),
                        \App\Services\CompanyFormService::makeJobTitleSelect(),

                        Select::make('employment_type')
                            ->label(__('employees.fields.employment_type'))
                            ->options(__('employees.employment_types'))
                            ->default('full_time'),

                        DatePicker::make('hire_date')
                            ->label(__('employees.fields.hire_date'))
                            ->native(false),

                        TextInput::make('annual_leave_days')
                            ->label(__('employees.fields.annual_leave_days'))
                            ->numeric()
                            ->minValue(0)
                            ->default(21)
                            ->suffix(__('employees.fields.annual_leave_days_suffix'))
                            ->helperText(__('employees.fields.annual_leave_days_helper'))
                            ->required(),

                        DatePicker::make('contract_end_date')
                            ->label(__('employees.fields.contract_end_date'))
                            ->native(false),

                        TextInput::make('contract_duration')
                            ->label(__('employees.fields.contract_duration')),

                        Select::make('status')
                            ->label(__('employees.fields.status'))
                            ->options(__('employees.statuses'))
                            ->default('active')
                            ->required(),
                    ]),

                Section::make(__('employees.sections.financial_info'))
                    ->description(__('employees.sections.financial_info_desc'))
                    ->icon('heroicon-o-banknotes')
                    ->columns(3)
                    ->schema([
                        TextInput::make('basic_salary')
                            ->label(__('employees.fields.basic_salary'))
                            ->numeric()
                            ->prefix(__('employees.fields.currency_sar'))
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0)),

                        TextInput::make('bank_iban')
                            ->label(__('employees.fields.bank_iban')),

                        TextInput::make('daily_work_hours')
                            ->label(__('employees.fields.daily_work_hours'))
                            ->numeric()
                            ->default(8),

                        TextInput::make('weekly_work_hours')
                            ->label(__('employees.fields.weekly_work_hours'))
                            ->numeric()
                            ->default(48),
                    ]),

                Section::make(__('employees.sections.attachments'))
                    ->description(__('employees.sections.attachments_desc'))
                    ->icon('heroicon-o-paper-clip')
                    ->columns(3)
                    ->schema([
                        FileUpload::make('iqama_file')
                            ->label(__('employees.fields.iqama_file'))
                            ->directory('employees/iqamas')
                            ->acceptedFileTypes(\App\Models\Setting::getAllowedUploadMimeTypes())
                            ->maxSize(\App\Models\Setting::getMaxUploadSizeKb())
                            ->openable()
                            ->downloadable(),

                        FileUpload::make('passport_file')
                            ->label(__('employees.fields.passport_file'))
                            ->directory('employees/passports')
                            ->acceptedFileTypes(\App\Models\Setting::getAllowedUploadMimeTypes())
                            ->maxSize(\App\Models\Setting::getMaxUploadSizeKb())
                            ->openable()
                            ->downloadable(),

                        FileUpload::make('contract_file')
                            ->label(__('employees.fields.contract_file'))
                            ->directory('employees/contracts')
                            ->acceptedFileTypes(\App\Models\Setting::getAllowedUploadMimeTypes())
                            ->maxSize(\App\Models\Setting::getMaxUploadSizeKb())
                            ->openable()
                            ->downloadable(),

                        Textarea::make('notes')
                            ->label(__('employees.fields.notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('employees.fields.dashboard_access_section'))
                    ->description(__('employees.fields.dashboard_access_desc'))
                    ->icon('heroicon-o-shield-check')
                    ->columns(2)
                    ->schema([
                        Select::make('roles')
                            ->label(__('employees.fields.role_label'))
                            ->options(fn () => \Spatie\Permission\Models\Role::where('name', '!=', 'Super Admin')->pluck('name', 'name'))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->placeholder(__('employees.fields.role_placeholder')),

                        TextInput::make('user_password')
                            ->label(__('employees.fields.password_login'))
                            ->password()
                            ->revealable()
                            ->placeholder(__('employees.fields.password_login_placeholder')),
                    ]),
            ]);
    }
}
