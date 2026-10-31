<?php

namespace App\Filament\Resources\InstallmentPayments\Pages;

use App\Filament\Resources\InstallmentPayments\InstallmentPaymentResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInstallmentPayment extends EditRecord
{
    protected static string $resource = InstallmentPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print_voucher')
                ->label(__('finance.installment_payments.actions.print_voucher'))
                ->icon('heroicon-o-printer')
                ->color('warning')
                ->url(fn () => route('system.print', ['type' => 'installment-payment', 'id' => $this->record->id]))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            Action::make('saveAndPrint')
                ->label(__('finance.installment_payments.actions.save_and_print'))
                ->icon('heroicon-o-printer')
                ->color('success')
                ->action(function () {
                    $this->save(shouldRedirect: false);
                    $url = route('system.print', ['type' => 'installment-payment', 'id' => $this->record->id]);
                    $this->js("window.open('{$url}', '_blank');");
                }),
            $this->getCancelFormAction(),
        ];
    }
}
