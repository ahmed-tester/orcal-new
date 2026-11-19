<?php

namespace App\Filament\Resources\Owners\Pages;

use App\Filament\Resources\Owners\OwnerResource;
use App\Services\OwnerFormService;
use App\Services\PersonCapacityService;
use Filament\Resources\Pages\CreateRecord;

class CreateOwner extends CreateRecord
{
    protected static string $resource = OwnerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $rawState = $this->form->getRawState();
        $data = OwnerFormService::resolveOwnerUserData($data, $rawState);

        $capacity = $data['person_capacity'] ?? $rawState['person_capacity'] ?? null;
        $metadata = PersonCapacityService::mergeIntoMetadata($data['metadata'] ?? [], $capacity, 'owner');
        unset($data['person_capacity']);

        return ['metadata' => $metadata] + $data;
    }
}
