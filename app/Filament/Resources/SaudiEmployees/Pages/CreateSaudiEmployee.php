<?php

namespace App\Filament\Resources\SaudiEmployees\Pages;

use App\Enums\UserType;
use App\Filament\Resources\SaudiEmployees\SaudiEmployeeResource;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeDetail;
use App\Models\Owner;
use App\Models\User;
use App\Services\EmployeeAgeService;
use App\Services\PhoneService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CreateSaudiEmployee extends CreateRecord
{
    protected static string $resource = SaudiEmployeeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $rawState = $this->form->getRawState();
        $registrationSource = $data['registration_source'] ?? $rawState['registration_source'] ?? 'manual';
        $selectedOwnerId = $data['selected_owner_id'] ?? $rawState['selected_owner_id'] ?? null;

        if ($registrationSource === 'from_owner' && ! empty($selectedOwnerId)) {
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

            // Update owner user account data if changes were provided in form
            $userUpdates = array_filter([
                'name' => $data['user_name'] ?? null,
                'email' => ! empty($data['user_email']) ? trim($data['user_email']) : null,
                'phone' => ! empty($data['user_phone']) ? PhoneService::normalize($data['user_phone']) : null,
            ]);
            if (! empty($data['user_password'])) {
                $userUpdates['password'] = Hash::make($data['user_password']);
            }
            if (! empty($userUpdates)) {
                $owner->user->update($userUpdates);
            }
        } else {
            $nationalId = $data['national_id'] ?? null;
            if (! empty($nationalId) && EmployeeDetail::where('national_id', $nationalId)->exists()) {
                throw ValidationException::withMessages([
                    'national_id' => __('employees.fields.errors.national_id_exists'),
                ]);
            }

            $email = ! empty($data['user_email'])
                ? trim($data['user_email'])
                : ('saudi_'.($nationalId ?? uniqid()).'@orcal.sa');

            if (User::where('email', $email)->exists()) {
                throw ValidationException::withMessages([
                    'user_email' => __('employees.fields.errors.email_exists'),
                ]);
            }

            $normalizedPhone = PhoneService::normalize($data['user_phone'] ?? null);
            if ($normalizedPhone && User::where('phone', $normalizedPhone)->exists()) {
                throw ValidationException::withMessages([
                    'user_phone' => __('validation.unique', ['attribute' => __('employees.fields.phone')]),
                ]);
            }

            $user = User::create([
                'name' => $data['user_name'] ?? 'Employee',
                'email' => $email,
                'phone' => $normalizedPhone,
                'password' => ! empty($data['user_password']) ? Hash::make($data['user_password']) : Hash::make('12345678'),
                'user_type' => UserType::SAUDI_EMPLOYEE,
                'status' => 'active',
            ]);

            $data['user_id'] = $user->id;
        }

        if (empty($data['company_id']) && ! empty($data['branch_id'])) {
            $branch = Branch::find($data['branch_id']);
            if ($branch) {
                $data['company_id'] = $branch->company_id;
            }
        }

        if (array_key_exists('name_en', $data)) {
            $metadata = $data['metadata'] ?? [];
            if (is_string($metadata)) {
                $metadata = json_decode($metadata, true) ?? [];
            }
            if (! empty($data['name_en'])) {
                $metadata['name_en'] = trim($data['name_en']);
            } else {
                unset($metadata['name_en']);
            }
            $data['metadata'] = $metadata;
            unset($data['name_en']);
        }

        unset($data['registration_source'], $data['selected_owner_id']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getRawState();

        EmployeeDetail::create([
            'employee_id' => $this->record->id,
            'nationality' => 'saudi',
            'national_id' => $data['national_id'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'metadata' => ! empty($data['birth_date']) ? ['age' => EmployeeAgeService::calculateAge($data['birth_date'])] : null,
            'address' => $data['address'] ?? null,
        ]);

        if (! empty($data['roles']) && $this->record->user) {
            $this->record->user->syncRoles($data['roles']);
        }
    }
}
