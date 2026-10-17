<?php

namespace App\Filament\Resources\ForeignEmployees\Pages;

use App\Filament\Resources\ForeignEmployees\ForeignEmployeeResource;
use App\Services\PhoneService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditForeignEmployee extends EditRecord
{
    protected static string $resource = ForeignEmployeeResource::class;

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
            $data['nationality'] = $details->nationality;
            $data['iqama_number'] = $details->iqama_number;
            $data['iqama_expiry'] = $details->iqama_expiry?->format('Y-m-d');
            $data['passport_number'] = $details->passport_number;
            $data['passport_expiry'] = $details->passport_expiry?->format('Y-m-d');
            $data['insurance_expiry'] = $details->insurance_expiry?->format('Y-m-d');
            $data['work_permit_number'] = $details->work_permit_number;
            $data['work_permit_expiry'] = $details->work_permit_expiry?->format('Y-m-d');
            $data['sponsor_name'] = $details->sponsor_name;
            $data['last_leave_return_date'] = $details->last_leave_return_date?->format('Y-m-d');
            $data['gender'] = $details->gender;
            $data['marital_status'] = $details->marital_status;
            $data['birth_date'] = $details->birth_date?->format('Y-m-d');
            $data['age'] = \App\Services\EmployeeAgeService::calculateAge($details->birth_date);
            $data['city'] = $details->city;
            $data['address'] = $details->address;
            $data['iqama_file'] = $details->iqama_file;
            $data['passport_file'] = $details->passport_file;

            if ($details->guarantor) {
                $guarantor = $details->guarantor;
                $data['guarantor_option'] = $guarantor->type ?? 'external';
                $data['guarantor_employee_id'] = $guarantor->employee_id;
                $data['guarantor_company_id'] = $guarantor->employee?->company_id ?? $guarantor->company_id;
                $data['guarantor_name'] = $guarantor->name;
                $data['guarantor_national_id'] = $guarantor->national_id;
                $data['guarantor_phone'] = $guarantor->phone;
                $data['guarantor_nationality'] = $guarantor->nationality;
                $data['guarantor_job_title'] = $guarantor->job_title;
                $data['guarantor_address'] = $guarantor->address;
                $data['guarantor_notes'] = $guarantor->notes;
            } else {
                $data['guarantor_option'] = 'none';
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->form->getRawState();
        $employee = $this->record;

        if ($employee->user) {
            $userUpdate = [
                'name' => $data['user_name'] ?? $employee->user->name,
                'email' => $data['user_email'] ?? $employee->user->email,
                'phone' => PhoneService::normalize($data['user_phone'] ?? $employee->user->phone),
            ];
            if (!empty($data['user_password'])) {
                $userUpdate['password'] = Hash::make($data['user_password']);
            }
            $employee->user->update($userUpdate);

            if (isset($data['roles'])) {
                $employee->user->syncRoles($data['roles']);
            }
        }

        $guarantor = \App\Services\GuarantorService::resolveGuarantor($data, $employee->company_id);

        $guarantorOption = $data['guarantor_option'] ?? null;
        $guarantorId = match ($guarantorOption) {
            'none' => null,
            'internal', 'external' => $guarantor?->id,
            default => $employee->details?->guarantor_id,
        };

        $employee->details()->updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'nationality' => $data['nationality'] ?? 'other',
                'iqama_number' => $data['iqama_number'] ?? null,
                'iqama_expiry' => $data['iqama_expiry'] ?? null,
                'passport_number' => $data['passport_number'] ?? null,
                'passport_expiry' => $data['passport_expiry'] ?? null,
                'insurance_expiry' => $data['insurance_expiry'] ?? null,
                'work_permit_number' => $data['work_permit_number'] ?? null,
                'work_permit_expiry' => $data['work_permit_expiry'] ?? null,
                'sponsor_name' => $guarantor ? $guarantor->name : ($data['sponsor_name'] ?? null),
                'guarantor_id' => $guarantorId,
                'last_leave_return_date' => $data['last_leave_return_date'] ?? null,
                'gender' => $data['gender'] ?? 'male',
                'marital_status' => $data['marital_status'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'metadata' => !empty($data['birth_date']) ? ['age' => \App\Services\EmployeeAgeService::calculateAge($data['birth_date'])] : null,
                'city' => $data['city'] ?? null,
                'address' => $data['address'] ?? null,
                'iqama_file'            => is_array($data['iqama_file'] ?? null)
                                            ? collect($data['iqama_file'])->first()
                                            : ($data['iqama_file'] ?? null),
                'passport_file'         => is_array($data['passport_file'] ?? null)
                                            ? collect($data['passport_file'])->first()
                                            : ($data['passport_file'] ?? null),
            ]
        );
    }
}
