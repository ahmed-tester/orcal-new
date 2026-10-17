<?php

namespace App\Filament\Resources\InstallmentPayments\Pages;

use App\Filament\Resources\InstallmentPayments\InstallmentPaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInstallmentPayment extends EditRecord
{
    protected static string $resource = InstallmentPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
