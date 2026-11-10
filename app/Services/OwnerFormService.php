<?php

namespace App\Services;

use App\Enums\UserType;
use App\Filament\Resources\Owners\Pages\CreateOwner;
use App\Filament\Resources\Owners\Pages\EditOwner;
use App\Models\Employee;
use App\Models\Owner;
use App\Models\User;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OwnerFormService
{
    /**
     * Get the registration source fields (Radio + Saudi Employee Select).
     *
     * @return array<\Filament\Forms\Components\Component>
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
                ->dehydrated()
                ->visible(fn ($livewire) => $livewire instanceof CreateOwner)
                ->columnSpanFull(),

            Select::make('selected_employee_id')
                ->label(__('owners.fields.selected_employee'))
                ->placeholder(__('owners.fields.selected_employee_placeholder'))
                ->options(fn () => static::getSaudiEmployeeOptions())
                ->searchable()
                ->preload()
                ->live()
                ->dehydrated()
                ->visible(fn ($get, $livewire) => $livewire instanceof CreateOwner && $get('registration_source') === 'from_saudi_employee')
                ->afterStateUpdated(fn ($state, callable $set) => static::populateFromEmployee($set, $state))
                ->columnSpanFull(),
        ];
    }

    /**
     * Get Saudi employee options for owner selection, excluding already registered owners.
     */
    public static function getSaudiEmployeeOptions(): array
    {
        $ownerUserIds = Owner::pluck('user_id')->filter()->unique();

        return Employee::saudi()
            ->whereNotIn('user_id', $ownerUserIds)
            ->with(['user', 'details'])
            ->get()
            ->mapWithKeys(fn ($e) => [
                $e->id => trim(($e->user?->name ?? '—') . ($e->details?->national_id ? " ({$e->details->national_id})" : '')),
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
        $set('user_email', $emp->user?->email);
        $set('user_phone', $emp->user?->phone);
        $set('national_id', $emp->details?->national_id);
        $set('nationality', $emp->details?->nationality ?? 'سعودي');
        $set('birth_date', $emp->details?->birth_date?->format('Y-m-d'));
        $set('company_id', $emp->company_id);
        $set('address', $emp->details?->address);
        $set('employment_date', $emp->hire_date?->format('Y-m-d') ?? now()->toDateString());
    }

    /**
     * Validation rule modifier to ignore selected employee's user ID for email & phone uniqueness.
     */
    public static function ignoreSelectedEmployeeUserId(): \Closure
    {
        return function ($rule, callable $get, $record) {
            $userId = $record?->user_id;

            if (! $userId && $get('registration_source') === 'from_saudi_employee' && $get('selected_employee_id')) {
                $userId = Employee::whereKey($get('selected_employee_id'))->value('user_id');
            }

            return $userId ? $rule->ignore($userId) : $rule;
        };
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
        $registrationSource = $data['registration_source'] ?? $rawState['registration_source'] ?? 'manual';
        $selectedEmployeeId = $data['selected_employee_id'] ?? $rawState['selected_employee_id'] ?? null;

        return $registrationSource === 'from_saudi_employee' && ! empty($selectedEmployeeId)
            ? static::handleEmployeeRegistration($data, (int) $selectedEmployeeId)
            : static::handleManualRegistration($data);
    }

    protected static function handleEmployeeRegistration(array $data, int $selectedEmployeeId): array
    {
        $employee = Employee::with('user')->findOrFail($selectedEmployeeId);

        if (! $employee->user) {
            throw ValidationException::withMessages([
                'selected_employee_id' => __('owners.fields.errors.employee_data_unavailable'),
            ]);
        }

        $companyId = $data['company_id'] ?? $employee->company_id;
        if ($companyId && Owner::where('user_id', $employee->user_id)->where('company_id', $companyId)->exists()) {
            throw ValidationException::withMessages([
                'selected_employee_id' => __('owners.fields.errors.employee_already_owner'),
            ]);
        }

        $data['user_id'] = $employee->user_id;

        $userUpdates = array_filter([
            'name' => $data['user_name'] ?? null,
            'phone' => ! empty($data['user_phone']) ? PhoneService::normalize($data['user_phone']) : null,
        ]);

        if (! empty($data['user_password'])) {
            $userUpdates['password'] = Hash::make($data['user_password']);
        }

        if (! empty($userUpdates)) {
            $employee->user->update($userUpdates);
        }

        return $data;
    }

    protected static function handleManualRegistration(array $data): array
    {
        $normalizedPhone = PhoneService::normalize($data['user_phone'] ?? null);
        $email = ! empty($data['user_email']) ? trim($data['user_email']) : ('owner_' . uniqid() . '@company.com');

        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'user_email' => __('employees.fields.errors.email_exists'),
            ]);
        }

        if ($normalizedPhone && User::where('phone', $normalizedPhone)->exists()) {
            throw ValidationException::withMessages([
                'user_phone' => __('validation.unique', ['attribute' => __('owners.fields.phone')]),
            ]);
        }

        $user = User::create([
            'name' => $data['user_name'] ?? 'Owner',
            'email' => $email,
            'phone' => $normalizedPhone,
            'password' => ! empty($data['user_password']) ? Hash::make($data['user_password']) : Hash::make('password'),
            'user_type' => UserType::OWNER,
            'status' => 'active',
        ]);

        $data['user_id'] = $user->id;

        return $data;
    }
}
