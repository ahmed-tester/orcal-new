<?php

namespace App\Filament\Resources\LoanFundMembers\Pages;

use App\Filament\Resources\LoanFundMembers\LoanFundMemberResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateLoanFundMember extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = LoanFundMemberResource::class;
}
