<?php

namespace App\Services;

use App\Filament\Resources\Owners\Pages\EditOwner;
use App\Models\Employee;
use App\Models\Owner;
use App\Models\User;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

class OwnerFormService
{
    /**
     * Get the registration source fields (Radio + Saudi Employee Select).
     *
     * @return array<Component>
     */
    public static function makeRegistrationFields(): array
    {
        return [
            Radio::make('registration_source')
                ->label(__('owners.fields.registration_source'))
                ->options([
                    'manual' => __('owners.fields.registration_manual'),
                    'from_saudi_employee' => __('owners.fields.registration_from_saudi_employee'),
                ])
                ->default('manual')
                ->inline()
                ->live()
                ->afterStateUpdated(fn ($state, callable $set) => $set('person_capacity', $state === 'from_saudi_employee' ? 'employee_and_owner' : 'owner'))
                ->dehydrated()
                ->visible(fn ($record) => $record === null)
                ->columnSpanFull(),

            Select::make('selected_employee_id')
                ->label(__('owners.fields.selected_employee'))
                ->placeholder(__('owners.fields.selected_employee_placeholder'))
                ->options(fn (callable $get) => static::getSaudiEmployeeOptions($get('company_id')))
                ->searchable()
                ->preload()
                ->live()
                ->dehydrated()
                ->visible(fn ($record, callable $get) => $record === null && $get('registration_source') === 'from_saudi_employee')
                ->afterStateUpdated(fn ($state, callable $set) => static::populateFromEmployee($set, $state))
                ->columnSpanFull(),
        ];
    }

    /**
     * Get owner financial fields.
     *
     * @return array<Component>
     */
    public static function makeFinancialFields(): array
    {
        return [
            TextInput::make('monthly_deduction_percentage')
                ->label(__('owners.fields.monthly_deduction_percentage'))
                ->numeric()
                ->suffix('%')
                ->minValue(0)
                ->maxValue(100)
                ->nullable(),

            TextInput::make('owner_advances')
                ->label(__('owners.fields.owner_advances'))
                ->numeric()
                ->prefix('ر.س')
                ->minValue(0)
                ->nullable(),

            TextInput::make('debts')
                ->label(__('owners.fields.debts'))
                ->numeric()
                ->prefix('ر.س')
                ->minValue(0)
                ->nullable(),

            static::makeAmountInput('other_amount', 'owners.fields.other_amount'),

            static::makeAmountInput('basic_salary', 'owners.fields.basic_salary'),

            Textarea::make('other_financials')
                ->label(__('owners.fields.other_financials'))
                ->placeholder(__('owners.fields.other_financials_placeholder'))
                ->rows(2)
                ->columnSpanFull()
                ->nullable(),
        ];
    }

    public static function makeAmountInput(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label(__($label))
            ->numeric()
            ->minValue(0)
            ->maxValue(999999999999.99)
            ->step('0.01')
            ->rule('decimal:0,2')
            ->suffix(__('owners.fields.currency'));
    }

    /**
     * Get Saudi employee options for owner selection, excluding already registered owners.
     */
    public static function getSaudiEmployeeOptions(?int $companyId = null): array
    {
        return Employee::saudi()
            ->when($companyId, function ($query, $companyId) {
                $query->whereDoesntHave('user.owners', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                });
            }, function ($query) {
                $query->whereDoesntHave('user.owners', function ($q) {
                    $q->whereColumn('owners.company_id', 'employees.company_id');
                });
            })
            ->with(['user', 'details'])
            ->get()
            ->mapWithKeys(fn ($e) => [
                $e->id => trim(($e->user?->name ?? '—').($e->details?->national_id ? " ({$e->details->national_id})" : '')),
            ])
            ->toArray();
    }

    /**
     * Auto-fill owner form fields from the selected Saudi employee.
     */
    public static function populateFromEmployee(callable $set, ?int $employeeId): void
    {
        if (! $employeeId || ! ($emp = Employee::with(['user', 'details'])->find($employeeId))) {
            return;
        }

        $set('user_name', $emp->user?->name);
        $set('name_en', $emp->name_en ?? $emp->metadata['name_en'] ?? null);
        $set('user_email', $emp->user?->email);
        $set('user_phone', $emp->user?->phone);
        $set('national_id', $emp->details?->national_id);
        $set('nationality', $emp->details?->nationality ?? 'سعودي');
        $set('birth_date', $emp->details?->birth_date?->format('Y-m-d'));
        $set('company_id', $emp->company_id);
        $set('address', $emp->details?->address);
        $set('employment_date', now()->toDateString());
        $set('person_capacity', 'employee_and_owner');
    }

    /**
     * Validation rule modifier to ignore selected employee's user ID for email & phone uniqueness.
     */
    public static function ignoreSelectedEmployeeUserId(): \Closure
    {
        return PersonCapacityService::ignoreLinkedUserRule('owner');
    }

    /**
     * Dynamic helper text for password field.
     */
    public static function getPasswordHelperText(): \Closure
    {
        return fn ($get, $livewire) => $livewire instanceof EditOwner
            ? __('owners.fields.password_help')
            : ($get('registration_source') === 'from_saudi_employee' ? __('owners.fields.password_help_employee') : null);
    }

    /**
     * Resolve and prepare user data for Owner creation.
     */
    public static function resolveOwnerUserData(array $data, array $rawState = []): array
    {
        return PersonCapacityService::resolveUserForRegistration('owner', $data, $rawState);
    }
}
