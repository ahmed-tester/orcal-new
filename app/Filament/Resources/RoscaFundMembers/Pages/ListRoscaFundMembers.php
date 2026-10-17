<?php

namespace App\Filament\Resources\RoscaFundMembers\Pages;

use App\Filament\Resources\RoscaFundMembers\RoscaFundMemberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoscaFundMembers extends ListRecords
{
    protected static string $resource = RoscaFundMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
