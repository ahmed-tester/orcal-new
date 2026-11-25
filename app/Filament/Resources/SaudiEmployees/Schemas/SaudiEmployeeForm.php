<?php

namespace App\Filament\Resources\SaudiEmployees\Schemas;

use App\Enums\DeductionBasis;
use App\Models\Company;
use App\Models\Owner;
use App\Models\User;
use App\Services\CompanyFormService;
use App\Services\EmployeeAgeService;
use App\Services\Payroll\EmployeeSalaryProfileService;
use App\Services\Payroll\SaudiPayrollPolicyService;
use App\Services\EmployeeServiceDurationService;
use App\Services\PersonCapacityService;
use App\Services\PhoneService;
use Carbon\Carbon;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Spatie\Permission\Models\Role;

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
                            ->afterStateUpdated(fn ($state, callable $set) => $set('person_capacity', $state === 'from_owner' ? 'employee_and_owner' : 'employee'))
                            ->visible(fn ($record) => $record === null)
                            ->columnSpanFull(),

                        Select::make('selected_owner_id')
                            ->label(__('employees.fields.selected_owner'))
                            ->options(fn () => Owner::with('user')
                                ->whereHas('user', fn ($q) => $q->whereDoesntHave('employee'))
                                ->get()
                                ->mapWithKeys(function ($owner) {
                                    $name = $owner->user?->name ?? '—';
                                    $nid = $owner->national_id ? " ({$owner->national_id})" : '';

                                    return [$owner->id => "{$name}{$nid}"];
                                }))
                            ->searchable()
                            ->preload()
                            ->live()
                            ->visible(fn ($record, callable $get) => $record === null && $get('registration_source') === 'from_owner')
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
                                    $set('person_capacity', 'employee_and_owner');
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
                            ->unique(User::class, 'email', ignoreRecord: false, modifyRuleUsing: PersonCapacityService::ignoreLinkedUserRule('employee')),

                        TextInput::make('national_id')
                            ->label(__('employees.fields.national_id'))
                            ->placeholder('1087654321')
                            ->length(10)
                            ->required(),

                        TextInput::make('profession')
                            ->label(__('employees.fields.profession'))
                            ->placeholder(__('employees.placeholders.profession'))
                            ->maxLength(255),

                        PersonCapacityService::makeFormField('employee'),

                        PhoneService::makeFormField('user_phone', __('employees.fields.phone'), required: true)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('user_phone', PhoneService::normalize($state) ?? $state))
                            ->unique(User::class, 'phone', ignoreRecord: false, modifyRuleUsing: PersonCapacityService::ignoreLinkedUserRule('employee')),

                        ...EmployeeAgeService::makeFormFields(),

                        TextInput::make('address')
                            ->label(__('employees.fields.address'))
                            ->placeholder(__('employees.placeholders.address')),

                        TextInput::make('bank_iban')
                            ->label(__('employees.fields.bank_iban'))
                            ->placeholder('SA03 8000 0000 6080 1016 7519')
                            ->columnSpan(2),

                        Textarea::make('notes')
                            ->label(__('employees.fields.notes'))
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('employees.salary_profile.sections.salary_details'))
                    ->description(__('employees.salary_profile.sections.salary_details_desc'))
                    ->icon('heroicon-o-banknotes')
                    ->columns(5)
                    ->schema([
                        TextInput::make('basic_salary')
                            ->label(__('employees.fields.basic_salary'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix(__('employees.fields.currency_sar'))
                            ->default(0)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (callable $get, callable $set) => self::updateTotalSalaryDisplay($get, $set))
                            ->dehydrateStateUsing(fn ($state) => round(max(0, (float) ($state ?? 0)), 2)),

                        TextInput::make('allowances_amount')
                            ->label(__('employees.salary_profile.fields.allowances_amount'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix(__('employees.fields.currency_sar'))
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (callable $get, callable $set) => self::updateTotalSalaryDisplay($get, $set))
                            ->dehydrateStateUsing(fn ($state) => round(max(0, (float) ($state ?? 0)), 2)),

                        TextInput::make('bonuses_amount')
                            ->label(__('employees.salary_profile.fields.bonuses_amount'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix(__('employees.fields.currency_sar'))
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (callable $get, callable $set) => self::updateTotalSalaryDisplay($get, $set))
                            ->dehydrateStateUsing(fn ($state) => round(max(0, (float) ($state ?? 0)), 2)),

                        TextInput::make('other_allowances_amount')
                            ->label(__('employees.salary_profile.fields.other_allowances_amount'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix(__('employees.fields.currency_sar'))
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (callable $get, callable $set) => self::updateTotalSalaryDisplay($get, $set))
                            ->dehydrateStateUsing(fn ($state) => round(max(0, (float) ($state ?? 0)), 2)),

                        TextInput::make('total_salary_display')
                            ->label(__('employees.salary_profile.fields.total_salary'))
                            ->numeric()
                            ->suffix(__('employees.fields.currency_sar'))
                            ->disabled()
                            ->dehydrated(false)
                            ->default(0),
                    ]),

                Section::make(__('employees.salary_profile.sections.overtime_compensation'))
                    ->description(__('employees.salary_profile.sections.overtime_compensation_desc'))
                    ->icon('heroicon-o-clock')
                    ->columns(2)
                    ->schema([
                        TextInput::make('overtime_hourly_rate')
                            ->label(__('employees.salary_profile.fields.overtime_hourly_rate'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix(__('employees.fields.currency_sar'))
                            ->placeholder(__('employees.salary_profile.placeholders.unspecified'))
                            ->helperText(__('employees.salary_profile.helpers.hourly_rate_unit')),

                        TextInput::make('compensation_hourly_rate')
                            ->label(__('employees.salary_profile.fields.compensation_hourly_rate'))
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix(__('employees.fields.currency_sar'))
                            ->placeholder(__('employees.salary_profile.placeholders.unspecified'))
                            ->helperText(__('employees.salary_profile.helpers.hourly_rate_unit')),
                    ]),

                Section::make(__('employees.salary_profile.sections.time_calculation'))
                    ->description(__('employees.salary_profile.sections.time_calculation_desc'))
                    ->icon('heroicon-o-calculator')
                    ->columns(4)
                    ->schema([
                        Select::make('deduction_basis')
                            ->label(__('employees.salary_profile.fields.deduction_basis'))
                            ->options(DeductionBasis::class)
                            ->default(DeductionBasis::BASIC->value)
                            ->live()
                            ->required()
                            ->helperText(__('employees.salary_profile.helpers.deduction_basis_pending')),

                        TextInput::make('daily_work_hours')
                            ->label(__('employees.fields.daily_work_hours'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(24)
                            ->step(0.5)
                            ->live(onBlur: true)
                            ->helperText(__('employees.salary_profile.helpers.employee_hours_source')),

                        TextInput::make('weekly_work_hours')
                            ->label(__('employees.fields.weekly_work_hours'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(168)
                            ->step(0.5)
                            ->live(onBlur: true)
                            ->helperText(__('employees.salary_profile.helpers.employee_hours_source')),

                        Placeholder::make('derived_hourly_rate_notice')
                            ->label(__('employees.salary_profile.fields.derived_hourly_rate'))
                            ->content(fn (callable $get): HtmlString => self::formatDerivedHourlyRatePreview($get))
                            ->columnSpan(2),
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
                            ->options(__('employees.employment_types'))
                            ->default('full_time'),

                        TextInput::make('annual_leave_days')
                            ->label(__('employees.fields.annual_leave_days'))
                            ->numeric()
                            ->minValue(0)
                            ->default(21)
                            ->suffix(__('employees.fields.annual_leave_days_suffix'))
                            ->helperText(__('employees.fields.annual_leave_days_helper'))
                            ->required(),

                        TextInput::make('contract_duration')
                            ->label(__('employees.fields.contract_duration')),

                        Select::make('status')
                            ->label(__('employees.fields.status'))
                            ->options(__('employees.statuses'))
                            ->default('active')
                            ->required(),

                        ...EmployeeServiceDurationService::makeFormFields(),
                    ]),

                Section::make(__('employees.fields.dashboard_access_section'))
                    ->description(__('employees.fields.dashboard_access_desc'))
                    ->icon('heroicon-o-shield-check')
                    ->columns(2)
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

    private static function updateTotalSalaryDisplay(callable $get, callable $set): void
    {
        $total = round(
            max(0, (float) ($get('basic_salary') ?? 0))
            + max(0, (float) ($get('allowances_amount') ?? 0))
            + max(0, (float) ($get('bonuses_amount') ?? 0))
            + max(0, (float) ($get('other_allowances_amount') ?? 0)),
            2
        );

        $set('total_salary_display', $total);
    }

    private static function formatDerivedHourlyRatePreview(callable $get): HtmlString
    {
        $dailyHours = (float) ($get('daily_work_hours') ?? 0);
        if ($dailyHours <= 0) {
            $msg = e(__('employees.salary_profile.placeholders.missing_daily_hours'));

            return new HtmlString(
                '<div style="border:1px solid #e5e7eb;border-radius:0.5rem;background:#f9fafb;padding:0.625rem 0.875rem;font-size:0.875rem;color:#6b7280;">'
                .$msg
                .'</div>'
            );
        }

        $basic = max(0, (float) ($get('basic_salary') ?? 0));
        $allowances = max(0, (float) ($get('allowances_amount') ?? 0));
        $bonuses = max(0, (float) ($get('bonuses_amount') ?? 0));
        $other = max(0, (float) ($get('other_allowances_amount') ?? 0));
        $basisRaw = $get('deduction_basis');
        $basis = $basisRaw instanceof DeductionBasis
            ? $basisRaw
            : (DeductionBasis::tryFrom((string) ($basisRaw ?? DeductionBasis::BASIC->value)) ?? DeductionBasis::BASIC);

        $baseAmount = match ($basis) {
            DeductionBasis::BASIC => $basic,
            DeductionBasis::BASIC_ALLOWANCES => round($basic + $allowances, 2),
            DeductionBasis::BASIC_BONUSES => round($basic + $bonuses, 2),
            DeductionBasis::TOTAL => round($basic + $allowances + $bonuses + $other, 2),
        };

        $companyId = $get('company_id');
        $company = $companyId ? Company::find((int) $companyId) : null;
        $policy = SaudiPayrollPolicyService::getCompanyPolicy($company);
        $currentYm = now()->format('Y-m');
        $days = (int) ($policy['monthly_days_overrides'][$currentYm] ?? SaudiPayrollPolicyService::getGlobalDefaultDays());
        $days = max(1, $days);

        $hourRate = ($baseAmount / $days) / $dailyHours;
        $rateText = e(number_format($hourRate, 2).' '.__('employees.salary_profile.placeholders.rate_per_hour_unit'));
        $formulaText = e(__('employees.salary_profile.placeholders.formula_breakdown', [
            'base' => number_format($baseAmount, 2),
            'days' => $days,
            'hours' => rtrim(rtrim(number_format($dailyHours, 2, '.', ''), '0'), '.'),
        ]));

        $badgeText = e(__('employees.salary_profile.placeholders.policy_active_badge'));
        $badgeStyle = 'background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;';

        return new HtmlString(
            '<div style="border:1px solid #e5e7eb;border-radius:0.5rem;background:#f9fafb;padding:0.625rem 0.875rem;">'
            .'<div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:0.5rem;">'
            .'<span style="font-size:1rem;font-weight:700;color:#111827;">'.$rateText.'</span>'
            .'<span style="display:inline-flex;align-items:center;border-radius:0.375rem;padding:0.125rem 0.5rem;font-size:0.75rem;font-weight:600;'.$badgeStyle.'">'.$badgeText.'</span>'
            .'</div>'
            .'<div style="margin-top:0.375rem;font-size:0.75rem;color:#6b7280;">'.$formulaText.'</div>'
            .'</div>'
        );
    }
}
