<?php

namespace App\Filament\Resources\TreasuryTransactions\Pages;

use App\Filament\Resources\TreasuryTransactions\TreasuryTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTreasuryTransaction extends CreateRecord
{
    protected static string $resource = TreasuryTransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['company_id'])) {
            $data['company_id'] = auth()->user()?->company_id ?? auth()->user()?->employee?->company_id;
        }

        if (!empty($data['employee_id']) && !empty($data['beneficiary_type'])) {
            if ($name = \App\Services\TreasuryBeneficiaryService::resolveEmployeeName($data['beneficiary_type'], $data['employee_id'])) {
                $data['recipient_or_payer'] = $name;
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
