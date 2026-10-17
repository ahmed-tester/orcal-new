<?php

namespace App\Filament\Resources\LoanFundLoanPayments\Pages;

use App\Filament\Resources\LoanFundLoanPayments\LoanFundLoanPaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLoanFundLoanPayment extends EditRecord
{
    protected static string $resource = LoanFundLoanPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
