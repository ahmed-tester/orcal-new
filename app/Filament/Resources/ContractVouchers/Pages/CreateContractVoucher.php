<?php

namespace App\Filament\Resources\ContractVouchers\Pages;

use App\Filament\Resources\ContractVouchers\ContractVoucherResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateContractVoucher extends CreateRecord
{
    protected static string $resource = ContractVoucherResource::class;

    public bool $shouldPrintAfterCreate = false;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            Action::make('createAndPrint')
                ->label(__('contract_vouchers.actions.save_and_print'))
                ->icon('heroicon-o-printer')
                ->color('success')
                ->action(function () {
                    $this->shouldPrintAfterCreate = true;
                    $this->create();
                }),
            $this->getCreateAnotherFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        if ($this->shouldPrintAfterCreate && $this->record) {
            return route('contract_vouchers.print', $this->record);
        }

        return $this->getResource()::getUrl('index');
    }
}
