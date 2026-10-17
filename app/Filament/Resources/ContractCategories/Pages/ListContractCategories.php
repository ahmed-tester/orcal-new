<?php

namespace App\Filament\Resources\ContractCategories\Pages;

use App\Filament\Resources\ContractCategories\ContractCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContractCategories extends ListRecords
{
    protected static string $resource = ContractCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('contract_categories.actions.create')),
        ];
    }
}
