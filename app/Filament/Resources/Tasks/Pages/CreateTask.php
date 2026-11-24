<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateTask extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = TaskResource::class;
}
