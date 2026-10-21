<?php

namespace App\Filament\Resources\PayrollRecords\Pages;

use App\Filament\Resources\PayrollRecords\PayrollRecordResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayrollRecord extends CreateRecord
{
    protected static string $resource = PayrollRecordResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        if (!empty($data['employee_id']) && !empty($data['payroll_month'])) {
            $month = \Carbon\Carbon::parse($data['payroll_month'])->startOfMonth()->toDateString();
            $companyId = $data['company_id'] ?? auth()->user()?->company_id;
            $opType = $data['operation_type'] ?? 'payroll';

            $existingCancelled = \App\Models\PayrollRecord::where('employee_id', $data['employee_id'])
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('operation_type', $opType)
                ->whereDate('payroll_month', $month)
                ->where('payment_status', \App\Enums\PaymentStatus::CANCELLED)
                ->first();

            if ($existingCancelled) {
                $existingCancelled->delete();
            }
        }

        return parent::handleRecordCreation($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', [
            'op'         => $this->record->operation_type ?? 'payroll',
            'company_id' => $this->record->company_id ?? null,
        ]);
    }
}
