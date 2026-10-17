<?php

namespace App\Filament\Resources\ContractVouchers\Pages;

use App\Filament\Resources\ContractVouchers\ContractVoucherResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContractVoucher extends EditRecord
{
    protected static string $resource = ContractVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
