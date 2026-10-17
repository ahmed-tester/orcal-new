<?php

namespace App\Filament\Resources\LoanFundMemberPayments\Pages;

use App\Filament\Resources\LoanFundMemberPayments\LoanFundMemberPaymentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLoanFundMemberPayments extends ListRecords
{
    protected static string $resource = LoanFundMemberPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
