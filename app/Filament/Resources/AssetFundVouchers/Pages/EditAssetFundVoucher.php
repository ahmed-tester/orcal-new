<?php

namespace App\Filament\Resources\AssetFundVouchers\Pages;

use App\Filament\Resources\AssetFundVouchers\AssetFundVoucherResource;
use App\Filament\Resources\AssetFundVouchers\Schemas\AssetFundVoucherForm;
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

    /**
     * Prefills the tax percentage derived from the stored tax amount and base,
     * because the percentage is a UI-only field and not a database column.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['tax_percentage'] = AssetFundVoucherForm::deriveTaxPercentage($this->getRecord());

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
