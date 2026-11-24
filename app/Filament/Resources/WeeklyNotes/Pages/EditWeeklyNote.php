<?php

namespace App\Filament\Resources\WeeklyNotes\Pages;

use App\Filament\Resources\WeeklyNotes\WeeklyNoteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Concerns\RedirectsToIndex;

class EditWeeklyNote extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = WeeklyNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
