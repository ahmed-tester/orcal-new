<?php

namespace App\Filament\Resources\RoscaFundPayments\Pages;

use App\Filament\Resources\RoscaFundPayments\RoscaFundPaymentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoscaFundPayments extends ListRecords
{
    protected static string $resource = RoscaFundPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
