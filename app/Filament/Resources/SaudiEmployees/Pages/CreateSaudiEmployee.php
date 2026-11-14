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
use App\Services\PersonCapacityService;
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
        $data = PersonCapacityService::resolveUserForRegistration('employee', $data, $rawState);

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

        $capacity = $data['person_capacity'] ?? $rawState['person_capacity'] ?? null;
        $data['metadata'] = PersonCapacityService::mergeIntoMetadata($data['metadata'] ?? [], $capacity, 'employee');

        unset($data['registration_source'], $data['selected_owner_id'], $data['person_capacity']);

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
