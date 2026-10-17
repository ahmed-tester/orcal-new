<?php

namespace App\Filament\Resources\PosDevices\Pages;

use App\Filament\Resources\PosDevices\PosDeviceResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePosDevice extends CreateRecord
{
    protected static string $resource = PosDeviceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['company_id'])) {
            $data['company_id'] = auth()->user()?->company_id ?? auth()->user()?->employee?->company_id;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
