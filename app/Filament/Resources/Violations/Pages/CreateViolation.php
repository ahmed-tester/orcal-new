<?php

namespace App\Filament\Resources\Violations\Pages;

use App\Filament\Resources\Violations\ViolationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateViolation extends CreateRecord
{
    protected static string $resource = ViolationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['recorded_by'] = auth()->id();

        $baseDate = !empty($data['violation_date']) ? \Carbon\Carbon::parse($data['violation_date']) : now();

        $data['correction_deadline'] = !empty($data['correction_deadline'])
            ? $data['correction_deadline']
            : $baseDate->copy()->addDays(14)->format('Y-m-d');

        $data['objection_deadline'] = !empty($data['objection_deadline'])
            ? $data['objection_deadline']
            : $baseDate->copy()->addDays(60)->format('Y-m-d');

        $data['grace_deadline'] = !empty($data['grace_deadline'])
            ? $data['grace_deadline']
            : $baseDate->copy()->addDays(90)->format('Y-m-d');

        $data['legal_deadline'] = !empty($data['legal_deadline'])
            ? $data['legal_deadline']
            : $baseDate->copy()->addDays(60)->format('Y-m-d');

        return $data;
    }
}
