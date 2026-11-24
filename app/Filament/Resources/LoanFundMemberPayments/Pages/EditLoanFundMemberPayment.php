<?php

namespace App\Filament\Resources\LoanFundMemberPayments\Pages;

use App\Filament\Resources\LoanFundMemberPayments\LoanFundMemberPaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\RedirectsToIndex;

class EditLoanFundMemberPayment extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = LoanFundMemberPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
