<?php

namespace App\Filament\Resources\ServiceTransactions\Pages;

use App\Filament\Resources\ServiceTransactions\ServiceTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceTransactions extends ListRecords
{
    protected static string $resource = ServiceTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
