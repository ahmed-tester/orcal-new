<?php

namespace App\Filament\Resources\Attendance\Pages;

use App\Filament\Resources\Attendance\AttendanceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAttendance extends CreateRecord
{
    protected static string $resource = AttendanceResource::class;

    public function mount(): void
    {
        parent::mount();

        $employeeId = request()->query('employee_id');
        $date = request()->query('date');

        if ($employeeId || $date) {
            $data = [];
            if ($employeeId) {
                $data['employee_id'] = (int) $employeeId;
            }
            if ($date) {
                $data['date'] = $date;
            }
            $this->form->fill($data);
        }
    }
}
