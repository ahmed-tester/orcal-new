<?php

namespace App\Filament\Resources\ContractCategories\Pages;

use App\Filament\Resources\ContractCategories\ContractCategoryResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateContractCategory extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = ContractCategoryResource::class;
}
