<?php

namespace App\Filament\Resources\AssetFundVouchers\Pages;

use App\Filament\Resources\AssetFundVouchers\AssetFundVoucherResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetFundVoucher extends CreateRecord
{
    protected static string $resource = AssetFundVoucherResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

