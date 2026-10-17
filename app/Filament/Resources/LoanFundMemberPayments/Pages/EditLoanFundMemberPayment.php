<?php

namespace App\Filament\Resources\LoanFundMemberPayments\Pages;

use App\Filament\Resources\LoanFundMemberPayments\LoanFundMemberPaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLoanFundMemberPayment extends EditRecord
{
    protected static string $resource = LoanFundMemberPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
