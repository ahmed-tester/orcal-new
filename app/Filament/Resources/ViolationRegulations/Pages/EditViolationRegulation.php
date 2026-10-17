<?php

namespace App\Filament\Resources\ViolationRegulations\Pages;

use App\Filament\Resources\ViolationRegulations\ViolationRegulationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditViolationRegulation extends EditRecord
{
    protected static string $resource = ViolationRegulationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
