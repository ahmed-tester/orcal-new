<?php

namespace App\Filament\Resources\ViolationTypes\Pages;

use App\Filament\Resources\ViolationTypes\ViolationTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateViolationType extends CreateRecord
{
    protected static string $resource = ViolationTypeResource::class;
}
