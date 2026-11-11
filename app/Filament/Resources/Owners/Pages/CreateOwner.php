<?php

namespace App\Filament\Resources\Owners\Pages;

use App\Filament\Resources\Owners\OwnerResource;
use App\Services\OwnerFormService;
use Filament\Resources\Pages\CreateRecord;

class CreateOwner extends CreateRecord
{
    protected static string $resource = OwnerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return OwnerFormService::resolveOwnerUserData($data, $this->form->getRawState());
    }
}
