<?php

namespace App\Filament\Resources\AssetFunds\Pages;

use App\Filament\Resources\AssetFunds\AssetFundResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssetFunds extends ListRecords
{
    protected static string $resource = AssetFundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

