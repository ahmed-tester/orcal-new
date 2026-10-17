<?php

namespace App\Filament\Resources\SupervisionShifts\Pages;

use App\Filament\Resources\SupervisionShifts\SupervisionShiftResource;
use App\Models\Employee;
use Filament\Resources\Pages\CreateRecord;

class CreateSupervisionShift extends CreateRecord
{
    protected static string $resource = SupervisionShiftResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        if (empty($data['supervisor_id']) && !empty($data['assigned_employees']) && is_array($data['assigned_employees'])) {
            $data['supervisor_id'] = $data['assigned_employees'][0] ?? null;
        }

        if (empty($data['company_id']) && !empty($data['supervisor_id'])) {
            $data['company_id'] = Employee::find($data['supervisor_id'])?->company_id;
        }

        return $data;
    }
}
