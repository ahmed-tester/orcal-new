<?php

namespace App\Filament\Resources\LoanFundLoans\Pages;

use App\Filament\Resources\LoanFundLoans\LoanFundLoanResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateLoanFundLoan extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = LoanFundLoanResource::class;
}
