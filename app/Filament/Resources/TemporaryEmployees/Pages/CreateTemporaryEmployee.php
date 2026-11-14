<?php

namespace App\Filament\Resources\TemporaryEmployees\Pages;

use App\Enums\EmploymentType;
use App\Enums\UserType;
use App\Filament\Resources\TemporaryEmployees\TemporaryEmployeeResource;
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

class CreateTemporaryEmployee extends CreateRecord
{
    protected static string $resource = TemporaryEmployeeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $rawState = $this->form->getRawState();
        $registrationSource = $data['registration_source'] ?? $rawState['registration_source'] ?? 'manual';
        $selectedOwnerId = $data['selected_owner_id'] ?? $rawState['selected_owner_id'] ?? null;
        $selectedSaudiEmployeeId = $data['selected_saudi_employee_id'] ?? $rawState['selected_saudi_employee_id'] ?? null;

        $metadata = $data['metadata'] ?? [];
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?? [];
        }
        $metadata['registration_source'] = $registrationSource;

        if (! empty($data['name_en'])) {
            $metadata['name_en'] = trim($data['name_en']);
        }
        if (! empty($data['termination_reason'])) {
            $metadata['termination_reason'] = $data['termination_reason'];
        }
        if (! empty($data['termination_reason_other'])) {
            $metadata['termination_reason_other'] = $data['termination_reason_other'];
        }

        if ($registrationSource === 'from_owner' && ! empty($selectedOwnerId)) {
            $owner = Owner::with('user')->find($selectedOwnerId);
            if (! $owner || ! $owner->user) {
                throw ValidationException::withMessages([
                    'selected_owner_id' => __('employees.fields.errors.owner_data_unavailable'),
                ]);
            }
            $data['user_id'] = $owner->user->id;
            $metadata['source_owner_id'] = $owner->id;

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
        } elseif ($registrationSource === 'from_saudi_employee' && ! empty($selectedSaudiEmployeeId)) {
            $emp = Employee::with('user')->find($selectedSaudiEmployeeId);
            if (! $emp || ! $emp->user) {
                throw ValidationException::withMessages([
                    'selected_saudi_employee_id' => 'بيانات الموظف السعودي المحدد غير متوفرة أو حسابه غير موجود.',
                ]);
            }
            $data['user_id'] = $emp->user->id;
            $metadata['source_employee_id'] = $emp->id;

            $userUpdates = array_filter([
                'name' => $data['user_name'] ?? null,
                'email' => ! empty($data['user_email']) ? trim($data['user_email']) : null,
                'phone' => ! empty($data['user_phone']) ? PhoneService::normalize($data['user_phone']) : null,
            ]);
            if (! empty($data['user_password'])) {
                $userUpdates['password'] = Hash::make($data['user_password']);
            }
            if (! empty($userUpdates)) {
                $emp->user->update($userUpdates);
            }
        } else {
            // Manual registration
            $nationalId = $data['national_id'] ?? null;
            $email = ! empty($data['user_email'])
                ? trim($data['user_email'])
                : ('temp_'.($nationalId ?? uniqid()).'@orcal.sa');

            $normalizedPhone = PhoneService::normalize($data['user_phone'] ?? null);

            $existingUser = User::where('email', $email)
                ->when($normalizedPhone, fn ($q) => $q->orWhere('phone', $normalizedPhone))
                ->first();

            if ($existingUser) {
                $data['user_id'] = $existingUser->id;
            } else {
                $user = User::create([
                    'name' => $data['user_name'] ?? 'Temporary Employee',
                    'email' => $email,
                    'phone' => $normalizedPhone,
                    'password' => ! empty($data['user_password']) ? Hash::make($data['user_password']) : Hash::make('12345678'),
                    'user_type' => UserType::SAUDI_EMPLOYEE,
                    'status' => 'active',
                ]);
                $data['user_id'] = $user->id;
            }
        }

        $data['employment_type'] = EmploymentType::TEMPORARY->value;
        $data['metadata'] = $metadata;

        if (empty($data['company_id']) && ! empty($data['branch_id'])) {
            $branch = Branch::find($data['branch_id']);
            if ($branch) {
                $data['company_id'] = $branch->company_id;
            }
        }

        unset(
            $data['registration_source'],
            $data['selected_owner_id'],
            $data['selected_saudi_employee_id'],
            $data['name_en'],
            $data['days_served'],
            $data['time_since_termination']
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getRawState();

        EmployeeDetail::create([
            'employee_id' => $this->record->id,
            'nationality' => 'saudi',
            'national_id' => $data['national_id'] ?? null,
            'profession' => $data['profession'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'metadata' => ! empty($data['birth_date']) ? ['age' => EmployeeAgeService::calculateAge($data['birth_date'])] : null,
            'address' => $data['address'] ?? null,
        ]);

        if (! empty($data['roles']) && $this->record->user) {
            $this->record->user->syncRoles($data['roles']);
        }
    }
}

