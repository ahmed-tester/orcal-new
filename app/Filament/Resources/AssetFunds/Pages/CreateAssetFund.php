<?php

namespace App\Filament\Resources\AssetFunds\Pages;

use App\Filament\Resources\AssetFunds\AssetFundResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetFund extends CreateRecord
{
    protected static string $resource = AssetFundResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

