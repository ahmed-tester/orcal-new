<?php

namespace App\Filament\Resources\PaymentCollectionItems\Pages;

use App\Filament\Resources\PaymentCollectionItems\PaymentCollectionItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPaymentCollectionItem extends EditRecord
{
    protected static string $resource = PaymentCollectionItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
