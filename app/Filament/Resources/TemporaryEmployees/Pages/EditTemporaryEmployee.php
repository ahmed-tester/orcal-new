<?php

namespace App\Filament\Resources\TemporaryEmployees\Pages;

use App\Filament\Resources\TemporaryEmployees\TemporaryEmployeeResource;
use App\Models\User;
use App\Services\EmployeeAgeService;
use App\Services\PhoneService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EditTemporaryEmployee extends EditRecord
{
    protected static string $resource = TemporaryEmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label(__('employees.actions.delete')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $employee = $this->record;
        $user = $employee->user;
        $details = $employee->details;

        if ($user) {
            $data['user_name'] = $user->name;
            $data['user_email'] = $user->email;
            $data['user_phone'] = $user->phone;
            $data['roles'] = $user->roles->pluck('name')->toArray();
        }

        if ($details) {
            $data['national_id'] = $details->national_id;
            $data['address'] = $details->address;
            $data['birth_date'] = $details->birth_date?->format('Y-m-d');
            $data['age'] = EmployeeAgeService::calculateAge($details->birth_date);
        }

        $data['name_en'] = $employee->metadata['name_en'] ?? null;
        $data['registration_source'] = $employee->metadata['registration_source'] ?? 'manual';
        $data['selected_owner_id'] = $employee->metadata['source_owner_id'] ?? null;
        $data['selected_saudi_employee_id'] = $employee->metadata['source_employee_id'] ?? null;
        $data['termination_reason'] = $employee->termination_reason;
        $data['termination_reason_other'] = $employee->termination_reason_other;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $employee = $this->record;
        $userId = $employee->user_id;

        $metadata = $employee->metadata ?? [];
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?? [];
        }

        if (array_key_exists('name_en', $data)) {
            if (! empty($data['name_en'])) {
                $metadata['name_en'] = trim($data['name_en']);
            } else {
                unset($metadata['name_en']);
            }
            unset($data['name_en']);
        }

        if (array_key_exists('termination_reason', $data)) {
            if (! empty($data['termination_reason'])) {
                $metadata['termination_reason'] = $data['termination_reason'];
            } else {
                unset($metadata['termination_reason']);
            }
        }

        if (array_key_exists('termination_reason_other', $data)) {
            if (! empty($data['termination_reason_other'])) {
                $metadata['termination_reason_other'] = $data['termination_reason_other'];
            } else {
                unset($metadata['termination_reason_other']);
            }
        }

        $data['metadata'] = $metadata;

        if (! empty($data['user_email'])) {
            $email = trim($data['user_email']);
            if (User::where('email', $email)->where('id', '!=', $userId)->exists()) {
                throw ValidationException::withMessages([
                    'user_email' => __('employees.fields.errors.email_exists'),
                ]);
            }
        }

        if (! empty($data['user_phone'])) {
            $normalizedPhone = PhoneService::normalize($data['user_phone']);
            if ($normalizedPhone && User::where('phone', $normalizedPhone)->where('id', '!=', $userId)->exists()) {
                throw ValidationException::withMessages([
                    'user_phone' => __('validation.unique', ['attribute' => __('employees.fields.phone')]),
                ]);
            }
        }

        unset(
            $data['registration_source'],
            $data['selected_owner_id'],
            $data['selected_saudi_employee_id'],
            $data['days_served'],
            $data['time_since_termination']
        );

        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->form->getRawState();
        $employee = $this->record;

        if ($employee->user) {
            $userUpdate = [
                'name' => $data['user_name'] ?? $employee->user->name,
                'email' => ! empty($data['user_email']) ? $data['user_email'] : $employee->user->email,
                'phone' => PhoneService::normalize($data['user_phone'] ?? $employee->user->phone),
            ];

            if (! empty($data['user_password'])) {
                $userUpdate['password'] = Hash::make($data['user_password']);
            }

            $employee->user->update($userUpdate);

            if (isset($data['roles'])) {
                $employee->user->syncRoles($data['roles']);
            }
        }

        $employee->details()->updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'nationality' => 'saudi',
                'national_id' => $data['national_id'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'metadata' => ! empty($data['birth_date']) ? ['age' => EmployeeAgeService::calculateAge($data['birth_date'])] : null,
                'address' => $data['address'] ?? null,
            ]
        );
    }
}

