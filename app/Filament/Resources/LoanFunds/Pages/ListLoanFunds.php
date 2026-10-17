<?php

namespace App\Filament\Resources\LoanFunds\Pages;

use App\Filament\Resources\LoanFunds\LoanFundResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLoanFunds extends ListRecords
{
    protected static string $resource = LoanFundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
