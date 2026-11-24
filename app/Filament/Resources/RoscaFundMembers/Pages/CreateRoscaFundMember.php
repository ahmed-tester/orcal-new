<?php

namespace App\Filament\Resources\RoscaFundMembers\Pages;

use App\Filament\Resources\RoscaFundMembers\RoscaFundMemberResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateRoscaFundMember extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = RoscaFundMemberResource::class;
}
