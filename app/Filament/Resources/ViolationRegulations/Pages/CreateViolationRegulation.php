<?php

namespace App\Filament\Resources\ViolationRegulations\Pages;

use App\Filament\Resources\ViolationRegulations\ViolationRegulationResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateViolationRegulation extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = ViolationRegulationResource::class;
}
