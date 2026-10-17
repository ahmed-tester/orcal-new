<?php

namespace App\Filament\Resources\SaudiEmployees\Pages;

use App\Filament\Resources\SaudiEmployees\SaudiEmployeeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSaudiEmployees extends ListRecords
{
    protected static string $resource = SaudiEmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
