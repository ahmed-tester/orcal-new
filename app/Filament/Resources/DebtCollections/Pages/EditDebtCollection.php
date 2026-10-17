<?php

namespace App\Filament\Resources\DebtCollections\Pages;

use App\Filament\Resources\DebtCollections\DebtCollectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDebtCollection extends EditRecord
{
    protected static string $resource = DebtCollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
