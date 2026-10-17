<?php

namespace App\Filament\Resources\ContractVouchers\Pages;

use App\Filament\Resources\ContractVouchers\ContractVoucherResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContractVouchers extends ListRecords
{
    protected static string $resource = ContractVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('contract_vouchers.actions.create')),
        ];
    }
}
