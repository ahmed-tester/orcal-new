<?php

namespace App\Filament\Resources\RoscaFunds\Pages;

use App\Filament\Resources\RoscaFunds\RoscaFundResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateRoscaFund extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = RoscaFundResource::class;
}
