<?php

namespace App\Filament\Resources\ContractVouchers\Pages;

use App\Filament\Resources\ContractVouchers\ContractVoucherResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContractVoucher extends EditRecord
{
    protected static string $resource = ContractVoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label(__('contract_vouchers.actions.print'))
                ->icon('heroicon-m-printer')
                ->color('warning')
                ->url(fn () => route('contract_vouchers.print', $this->record))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            Action::make('saveAndPrint')
                ->label(__('contract_vouchers.actions.save_and_print'))
                ->icon('heroicon-o-printer')
                ->color('success')
                ->action(function () {
                    $this->save(shouldRedirect: false);
                    $url = route('contract_vouchers.print', $this->record);
                    $this->js("window.open('{$url}', '_blank');");
                }),
            $this->getCancelFormAction(),
        ];
    }
}
