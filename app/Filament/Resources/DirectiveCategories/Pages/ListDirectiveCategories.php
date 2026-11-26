<?php

namespace App\Filament\Resources\DirectiveCategories\Pages;

use App\Filament\Resources\DirectiveCategories\DirectiveCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDirectiveCategories extends ListRecords
{
    protected static string $resource = DirectiveCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
