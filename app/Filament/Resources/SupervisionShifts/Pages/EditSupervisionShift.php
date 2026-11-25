<?php

namespace App\Filament\Resources\SupervisionShifts\Pages;

use App\Filament\Resources\SupervisionShifts\SupervisionShiftResource;
use App\Models\Employee;
use App\Models\SupervisionShift;
use App\Services\EmployeePerformanceAccessService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupervisionShift extends EditRecord
{
    protected static string $resource = SupervisionShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label(__('tasks.supervision_shifts.actions.print_shift'))
                ->icon('heroicon-o-printer')
                ->color('info')
                ->url(fn ($record) => route('supervision-shifts.print', $record))
                ->openUrlInNewTab(),
            DeleteAction::make()
                ->after(function (SupervisionShift $record): void {
                    $user = $record->supervisor?->user;
                    if ($user) {
                        EmployeePerformanceAccessService::revokeSupervisionPermission($user);
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['company_id']) && ! empty($data['supervisor_id'])) {
            $data['company_id'] = Employee::find($data['supervisor_id'])?->company_id;
        }

        return $data;
    }
}
