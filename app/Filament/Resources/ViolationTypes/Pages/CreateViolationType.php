<?php

namespace App\Filament\Resources\ViolationTypes\Pages;

use App\Filament\Resources\ViolationTypes\ViolationTypeResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateViolationType extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = ViolationTypeResource::class;
}
