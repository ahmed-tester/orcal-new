<?php

namespace App\Filament\Resources\DebtCollections\Pages;

use App\Filament\Resources\DebtCollections\DebtCollectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDebtCollections extends ListRecords
{
    protected static string $resource = DebtCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
