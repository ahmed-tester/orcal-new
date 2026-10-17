<?php

namespace App\Filament\Resources\WeeklyNotes\Pages;

use App\Filament\Resources\WeeklyNotes\WeeklyNoteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWeeklyNote extends EditRecord
{
    protected static string $resource = WeeklyNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
