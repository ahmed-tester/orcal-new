<?php

namespace App\Filament\Resources\SupervisionShifts\Pages;

use App\Filament\Resources\SupervisionShifts\SupervisionShiftResource;
use App\Models\Employee;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupervisionShift extends EditRecord
{
    protected static string $resource = SupervisionShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('print')
                ->label(__('tasks.supervision_shifts.actions.print_shift'))
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn ($record) => route('supervision-shifts.print', $record))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['supervisor_id']) && !empty($data['assigned_employees']) && is_array($data['assigned_employees'])) {
            $data['supervisor_id'] = $data['assigned_employees'][0] ?? null;
        }

        if (empty($data['company_id']) && !empty($data['supervisor_id'])) {
            $data['company_id'] = Employee::find($data['supervisor_id'])?->company_id;
        }

        return $data;
    }
}
