<?php

namespace App\Filament\Resources\Services\Pages;

use App\Filament\Resources\Services\ServiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['company_id']) || ($data['scope_type'] ?? null) === 'general' || !empty($data['beneficiary_name'])) {
            if (($data['scope_type'] ?? 'general') === 'general') {
                $data['company_id'] = null;
            }
        }

        return $data;
    }
}
