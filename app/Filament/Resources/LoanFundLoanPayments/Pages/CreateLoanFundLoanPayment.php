<?php

namespace App\Filament\Resources\LoanFundLoanPayments\Pages;

use App\Filament\Resources\LoanFundLoanPayments\LoanFundLoanPaymentResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateLoanFundLoanPayment extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = LoanFundLoanPaymentResource::class;
}
