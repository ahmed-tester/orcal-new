<?php

namespace App\Filament\Resources\PayrollRecords\Pages;

use App\Filament\Resources\PayrollRecords\PayrollRecordResource;
use App\Services\Payroll\PayrollEntitlementService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePayrollRecord extends CreateRecord
{
    protected static string $resource = PayrollRecordResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        if (($data['operation_type'] ?? 'payroll') === 'payroll' && ! empty($data['employee_id'])) {
            $month = (string) ($data['payroll_month'] ?? now()->format('Y-m'));
            $record = PayrollEntitlementService::upsertMonthlyPayroll((int) $data['employee_id'], $month, [
                'skip_if_financially_impacted' => false,
                'activate_if_new' => true,
                'record_date' => $data['record_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'overrides' => array_filter([
                    'basic_salary' => $data['basic_salary'] ?? null,
                ], fn ($v) => $v !== null),
            ]);

            if ($record) {
                return $record;
            }
        }

        return static::getModel()::create($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', [
            'op'         => $this->record->operation_type ?? 'payroll',
            'company_id' => $this->record->company_id ?? null,
        ]);
    }
}
