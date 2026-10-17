<?php

namespace App\Filament\Resources\LoanFundLoanPayments\Pages;

use App\Filament\Resources\LoanFundLoanPayments\LoanFundLoanPaymentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLoanFundLoanPayments extends ListRecords
{
    protected static string $resource = LoanFundLoanPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
