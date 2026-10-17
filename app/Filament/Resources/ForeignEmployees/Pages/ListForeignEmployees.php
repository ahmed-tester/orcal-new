<?php

namespace App\Filament\Resources\ForeignEmployees\Pages;

use App\Filament\Resources\ForeignEmployees\ForeignEmployeeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListForeignEmployees extends ListRecords
{
    protected static string $resource = ForeignEmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
