<?php

namespace App\Filament\Resources\ServiceTransactions\Pages;

use App\Filament\Resources\ServiceTransactions\ServiceTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServiceTransaction extends CreateRecord
{
    protected static string $resource = ServiceTransactionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
