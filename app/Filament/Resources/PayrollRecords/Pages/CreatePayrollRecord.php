<?php

namespace App\Filament\Resources\PayrollRecords\Pages;

use App\Filament\Resources\PayrollRecords\PayrollRecordResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePayrollRecord extends CreateRecord
{
    protected static string $resource = PayrollRecordResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index', [
            'op'         => $this->record->operation_type ?? 'payroll',
            'company_id' => $this->record->company_id ?? null,
        ]);
    }
}
