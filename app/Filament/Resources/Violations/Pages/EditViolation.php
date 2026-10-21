<?php

namespace App\Filament\Resources\Violations\Pages;

use App\Filament\Resources\Violations\ViolationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditViolation extends EditRecord
{
    protected static string $resource = ViolationResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $baseDate = !empty($data['violation_date']) ? \Carbon\Carbon::parse($data['violation_date']) : now();

        $data['correction_deadline'] = !empty($data['correction_deadline'])
            ? \Carbon\Carbon::parse($data['correction_deadline'])->format('Y-m-d')
            : $baseDate->copy()->addDays(14)->format('Y-m-d');

        $data['objection_deadline'] = !empty($data['objection_deadline'])
            ? \Carbon\Carbon::parse($data['objection_deadline'])->format('Y-m-d')
            : $baseDate->copy()->addDays(60)->format('Y-m-d');

        $data['grace_deadline'] = !empty($data['grace_deadline'])
            ? \Carbon\Carbon::parse($data['grace_deadline'])->format('Y-m-d')
            : $baseDate->copy()->addDays(90)->format('Y-m-d');

        $data['legal_deadline'] = !empty($data['legal_deadline'])
            ? \Carbon\Carbon::parse($data['legal_deadline'])->format('Y-m-d')
            : $baseDate->copy()->addDays(60)->format('Y-m-d');

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
