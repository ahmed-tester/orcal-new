<?php

namespace App\Filament\Resources\InstallmentPayments\Pages;

use App\Filament\Resources\InstallmentPayments\InstallmentPaymentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateInstallmentPayment extends CreateRecord
{
    protected static string $resource = InstallmentPaymentResource::class;

    public bool $shouldPrintAfterCreate = false;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            Action::make('createAndPrint')
                ->label(__('finance.installment_payments.actions.save_and_print'))
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
            return route('system.print', ['type' => 'installment-payment', 'id' => $this->record->id]);
        }

        return $this->getResource()::getUrl('index');
    }
}
