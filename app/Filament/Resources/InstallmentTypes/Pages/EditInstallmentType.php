<?php

namespace App\Filament\Resources\InstallmentTypes\Pages;

use App\Filament\Resources\InstallmentTypes\InstallmentTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInstallmentType extends EditRecord
{
    protected static string $resource = InstallmentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
