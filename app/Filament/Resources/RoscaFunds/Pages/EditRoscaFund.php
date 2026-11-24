<?php

namespace App\Filament\Resources\RoscaFunds\Pages;

use App\Filament\Resources\RoscaFunds\RoscaFundResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\RedirectsToIndex;

class EditRoscaFund extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = RoscaFundResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
