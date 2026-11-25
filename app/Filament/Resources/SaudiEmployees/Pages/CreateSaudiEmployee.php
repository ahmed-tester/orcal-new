<?php

namespace App\Filament\Resources\SaudiEmployees\Pages;

use App\Filament\Resources\SaudiEmployees\SaudiEmployeeResource;
use App\Models\Branch;
use App\Models\EmployeeDetail;
use App\Services\EmployeeAgeService;
use App\Services\Payroll\EmployeeSalaryProfileService;
use App\Services\PersonCapacityService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

        unset(
            $data['registration_source'],
            $data['selected_owner_id'],
            $data['person_capacity'],
            $data['allowances_amount'],
            $data['bonuses_amount'],
            $data['other_allowances_amount'],
            $data['total_salary_display'],
            $data['overtime_hourly_rate'],
            $data['compensation_hourly_rate'],
            $data['deduction_basis']
        );

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data): Model {
            $rawState = $this->form->getRawState();
            $record = parent::handleRecordCreation($data);

            EmployeeDetail::create([
                'employee_id' => $record->id,
                'nationality' => 'saudi',
                'national_id' => $rawState['national_id'] ?? null,
                'profession' => $rawState['profession'] ?? null,
                'birth_date' => $rawState['birth_date'] ?? null,
                'metadata' => ! empty($rawState['birth_date']) ? ['age' => EmployeeAgeService::calculateAge($rawState['birth_date'])] : null,
                'address' => $rawState['address'] ?? null,
            ]);

            if (! empty($rawState['roles']) && $record->user) {
                $record->user->syncRoles($rawState['roles']);
            }

            if (EmployeeSalaryProfileService::isEligibleSaudiEmployee($record)) {
                EmployeeSalaryProfileService::saveForSaudiEmployee($record, $rawState, auth()->user());
            }

            return $record;
        });
    }

    protected function afterCreate(): void {}
}
