<?php

namespace App\Filament\Resources\ServiceTransactions\Pages;

use App\Filament\Resources\ServiceTransactions\ServiceTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditServiceTransaction extends EditRecord
{
    protected static string $resource = ServiceTransactionResource::class;

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
