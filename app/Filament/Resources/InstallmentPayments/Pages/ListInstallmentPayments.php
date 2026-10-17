<?php

namespace App\Filament\Resources\InstallmentPayments\Pages;

use App\Filament\Resources\InstallmentPayments\InstallmentPaymentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInstallmentPayments extends ListRecords
{
    protected static string $resource = InstallmentPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
