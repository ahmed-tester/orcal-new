<?php

namespace App\Filament\Resources\OfficialLetters\Pages;

use App\Filament\Resources\OfficialLetters\OfficialLetterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOfficialLetters extends ListRecords
{
    protected static string $resource = OfficialLetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('official_letters.actions.create')),
        ];
    }
}
