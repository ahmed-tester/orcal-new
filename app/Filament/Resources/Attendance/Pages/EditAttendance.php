<?php

namespace App\Filament\Resources\Attendance\Pages;

use App\Filament\Resources\Attendance\AttendanceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAttendance extends EditRecord
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label(__('admin.delete')),
        ];
    }
}
