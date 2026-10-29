<?php

namespace App\Filament\Resources\ForeignEmployees\Pages;

use App\Enums\UserType;
use App\Filament\Resources\ForeignEmployees\ForeignEmployeeResource;
use App\Models\EmployeeDetail;
use App\Models\User;
use App\Services\EmployeeAgeService;
use App\Services\GuarantorService;
use App\Services\PhoneService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CreateForeignEmployee extends CreateRecord
{
    protected static string $resource = ForeignEmployeeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $iqamaNumber = $data['iqama_number'] ?? null;
        if (! empty($iqamaNumber) && EmployeeDetail::where('iqama_number', $iqamaNumber)->exists()) {
            throw ValidationException::withMessages([
                'iqama_number' => __('employees.fields.errors.iqama_exists'),
            ]);
        }

        if (! empty($data['user_email'])) {
            $email = trim($data['user_email']);
            if (User::where('email', $email)->exists()) {
                throw ValidationException::withMessages([
                    'user_email' => __('employees.fields.errors.email_exists'),
                ]);
            }
        } else {
            $email = 'foreign_'.uniqid().'@company.com';
        }

        $normalizedPhone = PhoneService::normalize($data['user_phone'] ?? null);
        if ($normalizedPhone && User::where('phone', $normalizedPhone)->exists()) {
            throw ValidationException::withMessages([
                'user_phone' => __('validation.unique', ['attribute' => __('employees.fields.phone')]),
            ]);
        }

        $user = User::create([
            'name' => $data['user_name'] ?? 'Resident Employee',
            'email' => $email,
            'phone' => $normalizedPhone,
            'password' => ! empty($data['user_password']) ? Hash::make($data['user_password']) : Hash::make('12345678'),
            'user_type' => UserType::FOREIGN_EMPLOYEE,
            'status' => 'active',
        ]);

        $data['user_id'] = $user->id;

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

        return $data;
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getRawState();

        $guarantor = GuarantorService::resolveGuarantor($data, $this->record->company_id);

        EmployeeDetail::create([
            'employee_id' => $this->record->id,
            'nationality' => $data['nationality'] ?? 'other',
            'iqama_number' => $data['iqama_number'] ?? null,
            'iqama_expiry' => $data['iqama_expiry'] ?? null,
            'passport_number' => $data['passport_number'] ?? null,
            'passport_expiry' => $data['passport_expiry'] ?? null,
            'insurance_expiry' => $data['insurance_expiry'] ?? null,
            'work_permit_number' => $data['work_permit_number'] ?? null,
            'work_permit_expiry' => $data['work_permit_expiry'] ?? null,
            'sponsor_name' => $guarantor ? $guarantor->name : ($data['sponsor_name'] ?? null),
            'guarantor_id' => $guarantor?->id,
            'last_leave_return_date' => $data['last_leave_return_date'] ?? null,
            'gender' => $data['gender'] ?? 'male',
            'marital_status' => $data['marital_status'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
            'metadata' => ! empty($data['birth_date']) ? ['age' => EmployeeAgeService::calculateAge($data['birth_date'])] : null,
            'city' => $data['city'] ?? null,
            'address' => $data['address'] ?? null,
            'iqama_file' => is_array($data['iqama_file'] ?? null)
                                        ? collect($data['iqama_file'])->first()
                                        : ($data['iqama_file'] ?? null),
            'passport_file' => is_array($data['passport_file'] ?? null)
                                        ? collect($data['passport_file'])->first()
                                        : ($data['passport_file'] ?? null),
        ]);

        if (! empty($data['roles']) && $this->record->user) {
            $this->record->user->syncRoles($data['roles']);
        }
    }
}
