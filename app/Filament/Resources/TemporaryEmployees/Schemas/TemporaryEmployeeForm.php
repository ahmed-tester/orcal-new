<?php

namespace App\Filament\Resources\TemporaryEmployees\Schemas;

use App\Models\Employee;
use App\Models\Owner;
use App\Models\User;
use App\Services\CompanyFormService;
use App\Services\EmployeeAgeService;
use App\Services\EmployeeServiceDurationService;
use App\Services\PhoneService;
use Carbon\Carbon;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class TemporaryEmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('بيانات التوظيف المؤقت والتسجيل')
                    ->icon('heroicon-o-user')
                    ->columns(4)
                    ->schema([
                        Radio::make('registration_source')
                            ->label(__('employees.fields.registration_source'))
                            ->options([
                                'manual' => __('employees.fields.registration_manual'),
                                'from_owner' => __('employees.fields.registration_from_owner'),
                                'from_saudi_employee' => __('employees.fields.registration_from_saudi_employee'),
                            ])
                            ->default('manual')
                            ->inline()
                            ->live()
                            ->columnSpanFull(),

                        Select::make('selected_owner_id')
                            ->label(__('employees.fields.selected_owner'))
                            ->options(fn () => Owner::with('user')->get()->mapWithKeys(function ($owner) {
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
                                $owner = Owner::with('user')->find($state);
                                if ($owner) {
                                    $set('user_name', $owner->user?->name);
                                    $set('user_email', $owner->user?->email);
                                    $set('national_id', $owner->national_id);
                                    $set('user_phone', $owner->user?->phone);
                                    if ($owner->birth_date) {
                                        $bdate = $owner->birth_date instanceof Carbon ? $owner->birth_date->format('Y-m-d') : (string) $owner->birth_date;
                                        $set('birth_date', $bdate);
                                        $set('age', EmployeeAgeService::calculateAge($bdate));
                                    }
                                    if ($owner->address) {
                                        $set('address', $owner->address);
                                    }
                                    if ($owner->company_id) {
                                        $set('company_id', $owner->company_id);
                                    }
                                }
                            })
                            ->columnSpanFull(),

                        Select::make('selected_saudi_employee_id')
                            ->label(__('employees.fields.selected_saudi_employee'))
                            ->options(fn () => Employee::saudi()->with(['user', 'details', 'company'])->get()->mapWithKeys(function ($emp) {
                                $name = $emp->user?->name ?? '—';
                                $nid = $emp->details?->national_id ? " ({$emp->details->national_id})" : '';
                                $comp = $emp->company ? " [{$emp->company->trade_name}]" : '';

                                return [$emp->id => "{$name}{$nid}{$comp}"];
                            }))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(fn ($get) => $get('registration_source') === 'from_saudi_employee')
                            ->afterStateUpdated(function ($state, $set) {
                                if (! $state) {
                                    return;
                                }
                                $emp = Employee::with(['user', 'details', 'jobTitle', 'company'])->find($state);
                                if ($emp) {
                                    $set('user_name', $emp->user?->name);
                                    $set('name_en', $emp->metadata['name_en'] ?? null);
                                    $set('user_email', $emp->user?->email);
                                    $set('user_phone', $emp->user?->phone);
                                    if ($emp->details?->national_id) {
                                        $set('national_id', $emp->details->national_id);
                                    }
                                    if ($emp->details?->profession) {
                                        $set('profession', $emp->details->profession);
                                    }
                                    if ($emp->details?->birth_date) {
                                        $bdate = $emp->details->birth_date instanceof Carbon ? $emp->details->birth_date->format('Y-m-d') : (string) $emp->details->birth_date;
                                        $set('birth_date', $bdate);
                                        $set('age', EmployeeAgeService::calculateAge($bdate));
                                    }
                                    if ($emp->details?->address) {
                                        $set('address', $emp->details->address);
                                    }
                                    if ($emp->company_id) {
                                        $set('company_id', $emp->company_id);
                                    }
                                    if ($emp->branch_id) {
                                        $set('branch_id', $emp->branch_id);
                                    }
                                    if ($emp->department_id) {
                                        $set('department_id', $emp->department_id);
                                    }
                                    if ($emp->job_title_id) {
                                        $set('job_title_id', $emp->job_title_id);
                                    }
                                    if ($emp->basic_salary) {
                                        $set('basic_salary', $emp->basic_salary);
                                    }
                                    if ($emp->bank_iban) {
                                        $set('bank_iban', $emp->bank_iban);
                                    }
                                    if ($emp->notes) {
                                        $set('notes', $emp->notes);
                                    }
                                }
                            })
                            ->columnSpanFull(),

                        TextInput::make('user_name')
                            ->label(__('employees.fields.name_ar'))
                            ->placeholder(__('employees.placeholders.name'))
                            ->required(),

                        TextInput::make('name_en')
                            ->label(__('employees.fields.name_en'))
                            ->placeholder(__('employees.placeholders.name_en')),

                        TextInput::make('user_email')
                            ->label(__('employees.fields.email'))
                            ->email()
                            ->placeholder('example@orcal.sa')
                            ->unique(User::class, 'email', ignoreRecord: false, modifyRuleUsing: function ($rule, callable $get, $record) {
                                $userId = $record?->user_id;
                                if (! $userId) {
                                    if ($get('registration_source') === 'from_owner' && $get('selected_owner_id')) {
                                        $owner = Owner::find($get('selected_owner_id'));
                                        $userId = $owner?->user_id;
                                    } elseif ($get('registration_source') === 'from_saudi_employee' && $get('selected_saudi_employee_id')) {
                                        $emp = Employee::find($get('selected_saudi_employee_id'));
                                        $userId = $emp?->user_id;
                                    }
                                }

                                return $userId ? $rule->ignore($userId) : $rule;
                            }),

                        TextInput::make('national_id')
                            ->label(__('employees.fields.national_id'))
                            ->placeholder('1087654321')
                            ->length(10)
                            ->required(),

                        TextInput::make('profession')
                            ->label(__('employees.fields.profession'))
                            ->placeholder(__('employees.placeholders.profession'))
                            ->maxLength(255),

                        PhoneService::makeFormField('user_phone', __('employees.fields.phone'), required: true)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('user_phone', PhoneService::normalize($state) ?? $state))
                            ->unique(User::class, 'phone', ignoreRecord: false, modifyRuleUsing: function ($rule, callable $get, $record) {
                                $userId = $record?->user_id;
                                if (! $userId) {
                                    if ($get('registration_source') === 'from_owner' && $get('selected_owner_id')) {
                                        $owner = Owner::find($get('selected_owner_id'));
                                        $userId = $owner?->user_id;
                                    } elseif ($get('registration_source') === 'from_saudi_employee' && $get('selected_saudi_employee_id')) {
                                        $emp = Employee::find($get('selected_saudi_employee_id'));
                                        $userId = $emp?->user_id;
                                    }
                                }

                                return $userId ? $rule->ignore($userId) : $rule;
                            }),

                        ...EmployeeAgeService::makeFormFields(),

                        TextInput::make('address')
                            ->label(__('employees.fields.address'))
                            ->placeholder(__('employees.placeholders.address')),

                        TextInput::make('basic_salary')
                            ->label(__('employees.fields.basic_salary'))
                            ->numeric()
                            ->suffix(__('employees.fields.currency_sar'))
                            ->default(0)
                            ->required()
                            ->dehydrateStateUsing(fn ($state) => (float) ($state ?? 0)),

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
                        CompanyFormService::makeCompanySelect(),
                        CompanyFormService::makeBranchSelect(),
                        CompanyFormService::makeDepartmentSelect(),
                        CompanyFormService::makeJobTitleSelect(),

                        Select::make('employment_type')
                            ->label(__('employees.fields.employment_type'))
                            ->options([
                                'temporary' => __('employees.employment_types.temporary') ?? 'مؤقت / موسمي',
                            ])
                            ->default('temporary')
                            ->disabled()
                            ->dehydrated(true),

                        TextInput::make('annual_leave_days')
                            ->label(__('employees.fields.annual_leave_days'))
                            ->numeric()
                            ->minValue(0)
                            ->default(21)
                            ->suffix(__('employees.fields.annual_leave_days_suffix'))
                            ->helperText(__('employees.fields.annual_leave_days_helper'))
                            ->required(),

                        EmployeeServiceDurationService::makeContractDurationField(),

                        Select::make('status')
                            ->label(__('employees.fields.status'))
                            ->options(__('employees.statuses'))
                            ->default('active')
                            ->required(),

                        ...EmployeeServiceDurationService::makeFormFields(autoContractDuration: true),
                    ]),

                Section::make(__('employees.fields.dashboard_access_section'))
                    ->description(__('employees.fields.dashboard_access_desc'))
                    ->icon('heroicon-o-shield-check')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Select::make('roles')
                            ->label(__('employees.fields.role_label'))
                            ->options(fn () => Role::where('name', '!=', 'Super Admin')->pluck('name', 'name'))
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

