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
}
