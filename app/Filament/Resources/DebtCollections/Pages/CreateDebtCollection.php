<?php

namespace App\Filament\Resources\DebtCollections\Pages;

use App\Filament\Resources\DebtCollections\DebtCollectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDebtCollection extends CreateRecord
{
    protected static string $resource = DebtCollectionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
