<?php

namespace App\Filament\Resources\LoanFunds\Pages;

use App\Filament\Resources\LoanFunds\LoanFundResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLoanFund extends EditRecord
{
    protected static string $resource = LoanFundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
