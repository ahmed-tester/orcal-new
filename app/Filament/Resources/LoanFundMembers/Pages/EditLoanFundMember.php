<?php

namespace App\Filament\Resources\LoanFundMembers\Pages;

use App\Filament\Resources\LoanFundMembers\LoanFundMemberResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\RedirectsToIndex;

class EditLoanFundMember extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = LoanFundMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
