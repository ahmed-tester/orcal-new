<?php

namespace App\Filament\Resources\LeaveTypes\Pages;

use App\Filament\Resources\LeaveTypes\LeaveTypeResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateLeaveType extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = LeaveTypeResource::class;
}
