<?php

namespace App\Filament\Resources\Installments\Pages;

use App\Filament\Resources\Installments\InstallmentResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateInstallment extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = InstallmentResource::class;
}
