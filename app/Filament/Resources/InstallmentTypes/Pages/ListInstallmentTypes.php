<?php

namespace App\Filament\Resources\InstallmentTypes\Pages;

use App\Filament\Resources\InstallmentTypes\InstallmentTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInstallmentTypes extends ListRecords
{
    protected static string $resource = InstallmentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
