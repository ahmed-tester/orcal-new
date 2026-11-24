<?php

namespace App\Filament\Resources\TaskAssignments\Pages;

use App\Filament\Resources\TaskAssignments\TaskAssignmentResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateTaskAssignment extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = TaskAssignmentResource::class;
}
