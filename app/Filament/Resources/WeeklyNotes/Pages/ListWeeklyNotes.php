<?php

namespace App\Filament\Resources\WeeklyNotes\Pages;

use App\Filament\Resources\WeeklyNotes\WeeklyNoteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWeeklyNotes extends ListRecords
{
    protected static string $resource = WeeklyNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
