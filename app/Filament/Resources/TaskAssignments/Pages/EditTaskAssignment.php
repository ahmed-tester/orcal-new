<?php

namespace App\Filament\Resources\TaskAssignments\Pages;

use App\Filament\Resources\TaskAssignments\TaskAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTaskAssignment extends EditRecord
{
    protected static string $resource = TaskAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $this->record->recalculateTotals();
    }
}
