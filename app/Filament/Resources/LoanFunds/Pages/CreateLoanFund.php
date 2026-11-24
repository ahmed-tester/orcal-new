<?php

namespace App\Filament\Resources\LoanFunds\Pages;

use App\Filament\Resources\LoanFunds\LoanFundResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateLoanFund extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = LoanFundResource::class;
}
