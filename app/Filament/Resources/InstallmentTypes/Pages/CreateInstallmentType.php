<?php

namespace App\Filament\Resources\InstallmentTypes\Pages;

use App\Filament\Resources\InstallmentTypes\InstallmentTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInstallmentType extends CreateRecord
{
    protected static string $resource = InstallmentTypeResource::class;
}
