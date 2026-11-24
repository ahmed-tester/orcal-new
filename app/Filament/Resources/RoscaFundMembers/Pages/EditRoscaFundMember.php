<?php

namespace App\Filament\Resources\RoscaFundMembers\Pages;

use App\Filament\Resources\RoscaFundMembers\RoscaFundMemberResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\RedirectsToIndex;

class EditRoscaFundMember extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = RoscaFundMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
