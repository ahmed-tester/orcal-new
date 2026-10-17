<?php

namespace App\Filament\Resources\RoscaFunds\Pages;

use App\Filament\Resources\RoscaFunds\RoscaFundResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoscaFunds extends ListRecords
{
    protected static string $resource = RoscaFundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
