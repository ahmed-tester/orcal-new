<?php

namespace App\Filament\Resources\PaymentCollectionItems\Pages;

use App\Filament\Resources\PaymentCollectionItems\PaymentCollectionItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentCollectionItems extends ListRecords
{
    protected static string $resource = PaymentCollectionItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
