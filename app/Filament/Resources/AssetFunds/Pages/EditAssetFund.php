<?php

namespace App\Filament\Resources\AssetFunds\Pages;

use App\Filament\Resources\AssetFunds\AssetFundResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssetFund extends EditRecord
{
    protected static string $resource = AssetFundResource::class;

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

