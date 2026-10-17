<?php

namespace App\Filament\Resources\ContractCategories\Pages;

use App\Filament\Resources\ContractCategories\ContractCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContractCategory extends EditRecord
{
    protected static string $resource = ContractCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
