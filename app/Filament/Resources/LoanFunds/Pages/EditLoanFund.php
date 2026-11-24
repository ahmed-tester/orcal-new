<?php

namespace App\Filament\Resources\LoanFunds\Pages;

use App\Filament\Resources\LoanFunds\LoanFundResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\RedirectsToIndex;

class EditLoanFund extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = LoanFundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
