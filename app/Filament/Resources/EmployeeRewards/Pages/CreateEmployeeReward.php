<?php

namespace App\Filament\Resources\EmployeeRewards\Pages;

use App\Filament\Resources\EmployeeRewards\EmployeeRewardResource;
use App\Models\Employee;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeeReward extends CreateRecord
{
    protected static string $resource = EmployeeRewardResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['company_id'])) {
            $employee = !empty($data['employee_id']) ? Employee::find($data['employee_id']) : null;
            $data['company_id'] = $employee?->company_id ?? auth()->user()?->company_id ?? auth()->user()?->employee?->company_id;
        }

        if (empty($data['created_by'])) {
            $data['created_by'] = auth()->id();
        }

        $hourly = (float)($data['hourly_rate'] ?? 0);
        $hours = (int)($data['hours_count'] ?? 0);
        $minutes = (int)($data['minutes_count'] ?? 0);

        $data['hours_amount'] = round($hourly * $hours, 2);
        $data['minute_rate'] = round($hourly / 60, 4);
        $data['minutes_amount'] = round($data['minute_rate'] * $minutes, 2);
        $data['net_amount'] = round($data['hours_amount'] + $data['minutes_amount'], 2);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
