<?php

namespace App\Filament\Resources\ViolationTypes\Pages;

use App\Filament\Resources\ViolationTypes\ViolationTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditViolationType extends EditRecord
{
    protected static string $resource = ViolationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
