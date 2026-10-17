<?php

namespace App\Filament\Resources\SaudiEmployees\Schemas;

use App\Services\EmployeeAgeService;
use App\Services\PhoneService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SaudiEmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('employees.fields.saudi_employee_data'))
                    ->icon('heroicon-o-user')
                    ->columns(4)
                    ->schema([
                        Radio::make('registration_source')
                            ->label(__('employees.fields.registration_source'))
                            ->options([
                                'manual' => __('employees.fields.registration_manual'),
                                'from_owner' => __('employees.fields.registration_from_owner'),
                            ])
                            ->default('manual')
                            ->inline()
                            ->live()
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        Select::make('selected_owner_id')
                            ->label(__('employees.fields.selected_owner'))
                            ->options(fn () => \App\Models\Owner::with('user')->get()->mapWithKeys(function ($owner) {
                                $name = $owner->user?->name ?? '—';
                                $nid = $owner->national_id ? " ({$owner->national_id})" : '';
                                return [$owner->id => "{$name}{$nid}"];
                            }))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(fn ($get) => $get('registration_source') === 'from_owner')
                            ->afterStateUpdated(function ($state, $set) {
                                if (! $state) {
                                    return;
                                }
                                $owner = \App\Models\Owner::with('user')->find($state);
                                if ($owner) {
                                    $set('user_name', $owner->user?->name);
                                    $set('user_email', $owner->user?->email);
                                    $set('national_id', $owner->national_id);
                                    $set('user_phone', $owner->user?->phone);
                                    if ($owner->birth_date) {
                                        $bdate = $owner->birth_date instanceof \Carbon\Carbon ? $owner->birth_date->format('Y-m-d') : (string)$owner->birth_date;
                                        $set('birth_date', $bdate);
                                        $set('age', \App\Services\EmployeeAgeService::calculateAge($bdate));
                                    }
                                    if ($owner->address) {
                                        $set('address', $owner->address);
                                    }
                                    if ($owner->company_id) {
                                        $set('company_id', $owner->company_id);
                                    }
                                }
                            })
                            ->dehydrated(false)
                            ->columnSpanFull(),

                        TextInput::make('user_name')
                            ->label(__('employees.fields.name'))
                            ->placeholder('أ. عبدالعزيز بن سالم المطيري')
                            ->required(),

                        TextInput::make('user_email')
                            ->label(__('employees.fields.email'))
                            ->email()
                            ->placeholder('example@orcal.sa'),

                        TextInput::make('national_id')
                            ->label(__('employees.fields.national_id'))
                            ->placeholder('1087654321')
                            ->length(10)
                            ->required(),

                        PhoneService::makeFormField('user_phone', __('employees.fields.phone'), required: true),

                        ...EmployeeAgeService::makeFormFields(),

                        TextInput::make('address')
                            ->label(__('employees.fields.address'))
                            ->placeholder('الرياض — حي الياسمين'),

                        TextInput::make('basic_salary')
                            ->label(__('employees.fields.basic_salary'))
                            ->numeric()
                            ->suffix(__('employees.fields.currency_sar'))
                            ->default(0)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => (float)($state ?? 0)),

                        TextInput::make('bank_iban')
                            ->label(__('employees.fields.bank_iban'))
                            ->placeholder('SA03 8000 0000 6080 1016 7519')
                            ->columnSpan(2),

                        Textarea::make('notes')
                            ->label(__('employees.fields.notes'))
                            ->rows(2)
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
