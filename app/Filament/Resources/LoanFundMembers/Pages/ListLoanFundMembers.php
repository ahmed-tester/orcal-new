<?php

namespace App\Filament\Resources\LoanFundMembers\Pages;

use App\Filament\Resources\LoanFundMembers\LoanFundMemberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLoanFundMembers extends ListRecords
{
    protected static string $resource = LoanFundMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
