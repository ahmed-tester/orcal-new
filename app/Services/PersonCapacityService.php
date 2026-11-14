<?php

namespace App\Services;

use App\Enums\UserType;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\Owner;
use App\Models\User;
use App\Services\PhoneService;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PersonCapacityService
{
    /**
     * Get the capacity options for either an employee or an owner.
     *
     * @return array<string, string>
     */
    public static function getOptions(string $for = 'employee'): array
    {
        if ($for === 'owner') {
            return [
                'owner' => __('owners.fields.capacity_owner'),
                'employee_and_owner' => __('owners.fields.capacity_employee_and_owner'),
            ];
        }

        return [
            'employee' => __('employees.fields.capacity_employee'),
            'employee_and_owner' => __('employees.fields.capacity_employee_and_owner'),
        ];
    }

    /**
     * Resolve the capacity key from stored metadata, falling back to relations.
     */
    public static function resolveKey(mixed $record, string $for = 'employee'): string
    {
        $isOwner = $for === 'owner' || $record instanceof Owner;
        $stored = is_array($record?->metadata) ? ($record->metadata['person_capacity'] ?? null) : null;

        if (is_string($stored) && array_key_exists($stored, static::getOptions($isOwner ? 'owner' : 'employee'))) {
            return $stored;
        }

        if ($isOwner) {
            return (bool) $record?->user?->employee ? 'employee_and_owner' : 'owner';
        }

        return ($record?->user?->owners?->isNotEmpty() ?? false) ? 'employee_and_owner' : 'employee';
    }

    /**
     * Merge the selected capacity into a record's metadata array.
     *
     * @param  array<string, mixed>|string|null  $metadata
     * @return array<string, mixed>
     */
    public static function mergeIntoMetadata(array|string|null $metadata, ?string $capacity, string $for = 'employee'): array
    {
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?? [];
        }

        $metadata = is_array($metadata) ? $metadata : [];
        $default = $for === 'owner' ? 'owner' : 'employee';
        $options = static::getOptions($for);

        $metadata['person_capacity'] = ($capacity && array_key_exists($capacity, $options)) ? $capacity : $default;

        return $metadata;
    }

    /**
     * Create the capacity Select form field for Create/Edit pages.
     */
    public static function makeFormField(string $for = 'employee'): Select
    {
        $isOwner = $for === 'owner';

        return Select::make('person_capacity')
            ->label($isOwner ? __('owners.fields.person_capacity') : __('employees.fields.person_capacity'))
            ->options(static::getOptions($for))
            ->default($isOwner ? 'owner' : 'employee')
            ->selectablePlaceholder(false)
            ->native(false)
            ->disabled()
            ->dehydrated();
    }

    public static function makeTableColumn(string $for = 'employee'): TextColumn
    {
        $isOwner = $for === 'owner';

        return TextColumn::make('person_capacity')
            ->label($isOwner ? __('owners.fields.person_capacity') : __('employees.fields.person_capacity'))
            ->state(function ($record) use ($for, $isOwner) {
                $key = static::resolveKey($record, $for);
                $options = static::getOptions($isOwner || $record instanceof Owner ? 'owner' : 'employee');

                return $options[$key] ?? ($isOwner ? __('owners.fields.capacity_owner') : __('employees.fields.capacity_employee'));
            })
            ->badge()
            ->color(function ($record) use ($for, $isOwner) {
                $key = static::resolveKey($record, $for);

                if ($key === 'employee_and_owner') {
                    return 'warning';
                }

                return ($isOwner || $record instanceof Owner) ? 'success' : 'info';
            })
            ->icon(function ($record) use ($for, $isOwner) {
                $key = static::resolveKey($record, $for);

                if ($key === 'employee_and_owner') {
                    return 'heroicon-m-briefcase';
                }

                return ($isOwner || $record instanceof Owner) ? 'heroicon-m-building-office-2' : 'heroicon-m-user';
            });
    }

    /**
     * Create the capacity table filter.
     */
    public static function makeTableFilter(string $for = 'employee'): SelectFilter
    {
        $isOwner = $for === 'owner';
        $relation = $isOwner ? 'user.employee' : 'user.owners';
        $onlyKey = $isOwner ? 'owner' : 'employee';

        return SelectFilter::make('person_capacity')
            ->label($isOwner ? __('owners.fields.person_capacity') : __('employees.fields.person_capacity'))
            ->options(static::getOptions($for))
            ->query(function ($query, array $data) use ($relation, $onlyKey) {
                $value = $data['value'] ?? null;
                if (! $value) {
                    return;
                }

                $query->where(function ($q) use ($value, $relation, $onlyKey) {
                    $q->where('metadata->person_capacity', $value)
                        ->orWhere(function ($fallback) use ($value, $relation, $onlyKey) {
                            $fallback->whereNull('metadata->person_capacity');
                            if ($value === 'employee_and_owner') {
                                $fallback->whereHas($relation);
                            } elseif ($value === $onlyKey) {
                                $fallback->whereDoesntHave($relation);
                            }
                        });
                });
            });
    }

    /**
     * Validation rule modifier to ignore linked user ID for email & phone uniqueness.
     *
     * @param string $for 'employee' or 'owner'
     */
    public static function ignoreLinkedUserRule(string $for = 'employee'): \Closure
    {
        return function ($rule, callable $get, $record) use ($for) {
            $userId = $record?->user_id;

            if (! $userId) {
                if ($for === 'employee' && $get('registration_source') === 'from_owner' && $get('selected_owner_id')) {
                    $userId = Owner::whereKey($get('selected_owner_id'))->value('user_id');
                } elseif ($for === 'owner' && $get('registration_source') === 'from_saudi_employee' && $get('selected_employee_id')) {
                    $userId = Employee::whereKey($get('selected_employee_id'))->value('user_id');
                }
            }

            return $userId ? $rule->ignore($userId) : $rule;
        };
    }

    /**
     * Symmetrical user provisioning/linking for employee or owner registration.
     *
     * @param string $targetType 'employee' or 'owner'
     * @param array $data
     * @param array $rawState
     * @return array
     */
    public static function resolveUserForRegistration(string $targetType, array $data, array $rawState = []): array
    {
        $registrationSource = $data['registration_source'] ?? $rawState['registration_source'] ?? 'manual';

        return match ($targetType) {
            'owner' => static::resolveOwnerRegistration($registrationSource, $data, $rawState),
            'employee' => static::resolveEmployeeRegistration($registrationSource, $data, $rawState),
            default => $data,
        };
    }

    /**
     * Resolve user for Owner registration (linked from Saudi employee or new manual).
     */
    protected static function resolveOwnerRegistration(string $source, array $data, array $rawState): array
    {
        $selectedEmployeeId = $data['selected_employee_id'] ?? $rawState['selected_employee_id'] ?? null;

        if ($source === 'from_saudi_employee' && ! empty($selectedEmployeeId)) {
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
            static::updateLinkedUser($employee->user, $data);

            return $data;
        }

        // Manual Owner registration
        $normalizedPhone = PhoneService::normalize($data['user_phone'] ?? null);
        $email = ! empty($data['user_email']) ? trim($data['user_email']) : ('owner_' . uniqid() . '@company.com');

        static::ensureUniqueEmailAndPhone($email, $normalizedPhone, __('owners.fields.phone'));

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

    /**
     * Resolve user for Employee registration (linked from Owner or new manual).
     */
    protected static function resolveEmployeeRegistration(string $source, array $data, array $rawState): array
    {
        $selectedOwnerId = $data['selected_owner_id'] ?? $rawState['selected_owner_id'] ?? null;

        if ($source === 'from_owner' && ! empty($selectedOwnerId)) {
            $owner = Owner::with('user')->find($selectedOwnerId);
            if (! $owner || ! $owner->user) {
                throw ValidationException::withMessages([
                    'selected_owner_id' => __('employees.fields.errors.owner_data_unavailable'),
                ]);
            }

            if (Employee::where('user_id', $owner->user->id)->exists()) {
                throw ValidationException::withMessages([
                    'selected_owner_id' => __('employees.fields.errors.owner_already_employee'),
                ]);
            }

            $data['user_id'] = $owner->user->id;
            static::updateLinkedUser($owner->user, $data);

            return $data;
        }

        // Manual Saudi employee registration
        $nationalId = $data['national_id'] ?? null;
        if (! empty($nationalId) && EmployeeDetail::where('national_id', $nationalId)->exists()) {
            throw ValidationException::withMessages([
                'national_id' => __('employees.fields.errors.national_id_exists'),
            ]);
        }

        $email = ! empty($data['user_email'])
            ? trim($data['user_email'])
            : ('saudi_' . ($nationalId ?? uniqid()) . '@orcal.sa');

        $normalizedPhone = PhoneService::normalize($data['user_phone'] ?? null);

        static::ensureUniqueEmailAndPhone($email, $normalizedPhone, __('employees.fields.phone'));

        $user = User::create([
            'name' => $data['user_name'] ?? 'Employee',
            'email' => $email,
            'phone' => $normalizedPhone,
            'password' => ! empty($data['user_password']) ? Hash::make($data['user_password']) : Hash::make('12345678'),
            'user_type' => UserType::SAUDI_EMPLOYEE,
            'status' => 'active',
        ]);

        $data['user_id'] = $user->id;

        return $data;
    }

    /**
     * Update an existing linked user's profile with supplied registration data.
     */
    protected static function updateLinkedUser(User $user, array $data): void
    {
        $email = ! empty($data['user_email']) ? trim($data['user_email']) : null;
        if ($email && User::where('email', $email)->where('id', '!=', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'user_email' => __('employees.fields.errors.email_exists'),
            ]);
        }

        $userUpdates = array_filter([
            'name' => $data['user_name'] ?? null,
            'email' => $email,
            'phone' => ! empty($data['user_phone']) ? PhoneService::normalize($data['user_phone']) : null,
        ]);

        if (! empty($data['user_password'])) {
            $userUpdates['password'] = Hash::make($data['user_password']);
        }

        if (! empty($userUpdates)) {
            $user->update($userUpdates);
        }
    }

    /**
     * Validate that email and normalized phone are unique in the users table.
     */
    protected static function ensureUniqueEmailAndPhone(string $email, ?string $normalizedPhone, string $phoneAttribute): void
    {
        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'user_email' => __('employees.fields.errors.email_exists'),
            ]);
        }

        if ($normalizedPhone && User::where('phone', $normalizedPhone)->exists()) {
            throw ValidationException::withMessages([
                'user_phone' => __('validation.unique', ['attribute' => $phoneAttribute]),
            ]);
        }
    }

    /**
     * Safely cleanup an orphaned user upon model deletion, or revert capacity on the remaining record.
     *
     * @param int|null $userId
     * @param string $fromType 'owner' or 'employee'
     * @param int|null $currentRecordId
     * @return bool Whether the user account was deleted
     */
    public static function cleanupOrphanedUser(?int $userId, string $fromType, ?int $currentRecordId = null): bool
    {
        if (! $userId || ! ($user = User::find($userId))) {
            return false;
        }

        if (static::isProtectedUser($user)) {
            return false;
        }

        $hasRemainingOwner = Owner::where('user_id', $userId)
            ->when($fromType === 'owner' && $currentRecordId, fn ($q) => $q->where('id', '!=', $currentRecordId))
            ->exists();

        $hasRemainingEmployee = Employee::where('user_id', $userId)
            ->when($fromType === 'employee' && $currentRecordId, fn ($q) => $q->where('id', '!=', $currentRecordId))
            ->exists();

        if (! $hasRemainingOwner && ! $hasRemainingEmployee) {
            return (bool) $user->delete();
        }

        static::revertRemainingCapacity($userId, $hasRemainingOwner, $hasRemainingEmployee, $fromType, $currentRecordId);

        return false;
    }

    /**
     * Check if the user account is an administrative account that must never be auto-deleted.
     */
    protected static function isProtectedUser(User $user): bool
    {
        if (in_array($user->user_type, [UserType::SUPER_ADMIN, UserType::ADMIN], true)) {
            return true;
        }

        try {
            return method_exists($user, 'hasRole') && ($user->hasRole('super_admin') || $user->hasRole('admin'));
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Revert capacity on the remaining model record when the other side is removed.
     */
    protected static function revertRemainingCapacity(int $userId, bool $hasOwner, bool $hasEmployee, string $fromType, ?int $currentRecordId): void
    {
        if (! $hasOwner && $hasEmployee) {
            $employee = Employee::where('user_id', $userId)
                ->when($fromType === 'employee' && $currentRecordId, fn ($q) => $q->where('id', '!=', $currentRecordId))
                ->first();

            if ($employee) {
                $employee->metadata = static::mergeIntoMetadata($employee->metadata, 'employee', 'employee');
                $employee->saveQuietly();
            }
        } elseif (! $hasEmployee && $hasOwner) {
            $owners = Owner::where('user_id', $userId)
                ->when($fromType === 'owner' && $currentRecordId, fn ($q) => $q->where('id', '!=', $currentRecordId))
                ->get();

            foreach ($owners as $owner) {
                $owner->metadata = static::mergeIntoMetadata($owner->metadata, 'owner', 'owner');
                $owner->saveQuietly();
            }
        }
    }
}
