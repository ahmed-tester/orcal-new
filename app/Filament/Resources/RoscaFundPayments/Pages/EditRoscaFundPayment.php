<?php

namespace App\Filament\Resources\RoscaFundPayments\Pages;

use App\Filament\Resources\RoscaFundPayments\RoscaFundPaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\RedirectsToIndex;

class EditRoscaFundPayment extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = RoscaFundPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
