<?php

namespace App\Filament\Resources\LoanFundMembers\Pages;

use App\Filament\Resources\LoanFundMembers\LoanFundMemberResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLoanFundMember extends EditRecord
{
    protected static string $resource = LoanFundMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
