<?php

namespace App\Filament\Resources\LoanFundLoans\Pages;

use App\Filament\Resources\LoanFundLoans\LoanFundLoanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLoanFundLoan extends EditRecord
{
    protected static string $resource = LoanFundLoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
