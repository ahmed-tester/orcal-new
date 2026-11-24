<?php

namespace App\Filament\Resources\LoanFundLoans\Pages;

use App\Filament\Resources\LoanFundLoans\LoanFundLoanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\RedirectsToIndex;

class EditLoanFundLoan extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = LoanFundLoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
