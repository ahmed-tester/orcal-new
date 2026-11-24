<?php

namespace App\Filament\Resources\InstallmentTypes\Pages;

use App\Filament\Resources\InstallmentTypes\InstallmentTypeResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateInstallmentType extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = InstallmentTypeResource::class;
}
