<?php

namespace App\Filament\Resources\AssetFundVouchers\Pages;

use App\Filament\Resources\AssetFundVouchers\AssetFundVoucherResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssetFundVoucher extends EditRecord
{
    protected static string $resource = AssetFundVoucherResource::class;

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

