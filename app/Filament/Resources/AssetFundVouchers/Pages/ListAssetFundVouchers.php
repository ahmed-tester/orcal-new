<?php

namespace App\Filament\Resources\AssetFundVouchers\Pages;

use App\Filament\Resources\AssetFundVouchers\AssetFundVoucherResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssetFundVouchers extends ListRecords
{
    protected static string $resource = AssetFundVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

