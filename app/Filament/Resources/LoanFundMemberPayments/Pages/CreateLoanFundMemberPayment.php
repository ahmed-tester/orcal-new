<?php

namespace App\Filament\Resources\LoanFundMemberPayments\Pages;

use App\Filament\Resources\LoanFundMemberPayments\LoanFundMemberPaymentResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateLoanFundMemberPayment extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = LoanFundMemberPaymentResource::class;
}
