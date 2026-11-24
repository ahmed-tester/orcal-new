<?php

namespace App\Filament\Resources\ContractCategories\Pages;

use App\Filament\Resources\ContractCategories\ContractCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\RedirectsToIndex;

class EditContractCategory extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = ContractCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
