<?php

namespace App\Filament\Resources\SaudiEmployees\Pages;

use App\Filament\Resources\SaudiEmployees\SaudiEmployeeResource;
use App\Services\PhoneService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditSaudiEmployee extends EditRecord
{
    protected static string $resource = SaudiEmployeeResource::class;

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
            $data['age'] = \App\Services\EmployeeAgeService::calculateAge($details->birth_date);
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
                'email' => !empty($data['user_email']) ? $data['user_email'] : $employee->user->email,
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

        $employee->details()->updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'nationality' => 'saudi',
                'national_id' => $data['national_id'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'metadata' => !empty($data['birth_date']) ? ['age' => \App\Services\EmployeeAgeService::calculateAge($data['birth_date'])] : null,
                'address' => $data['address'] ?? null,
            ]
        );
    }
}
