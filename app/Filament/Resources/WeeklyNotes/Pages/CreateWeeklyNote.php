<?php

namespace App\Filament\Resources\WeeklyNotes\Pages;

use App\Filament\Resources\WeeklyNotes\WeeklyNoteResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateWeeklyNote extends CreateRecord
{
    protected static string $resource = WeeklyNoteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        if (blank($data['voucher_number'] ?? null)) {
            $data['voucher_number'] = 'WN-' . date('Ymd') . '-' . rand(100, 999);
        }

        return $data;
    }
}
