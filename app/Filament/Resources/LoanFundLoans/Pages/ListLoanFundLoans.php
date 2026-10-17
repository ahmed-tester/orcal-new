<?php

namespace App\Filament\Resources\LoanFundLoans\Pages;

use App\Filament\Resources\LoanFundLoans\LoanFundLoanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLoanFundLoans extends ListRecords
{
    protected static string $resource = LoanFundLoanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
