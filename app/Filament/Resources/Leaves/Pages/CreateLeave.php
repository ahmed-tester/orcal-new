<?php

namespace App\Filament\Resources\Leaves\Pages;

use App\Filament\Resources\Leaves\LeaveResource;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Concerns\RedirectsToIndex;

class CreateLeave extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = LeaveResource::class;

    public function mount(): void
    {
        parent::mount();

        $employeeId = request()->query('employee_id');
        if ($employeeId) {
            $this->form->fill([
                'employee_id' => (int) $employeeId,
                'start_date' => now()->toDateString(),
            ]);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['status'] ?? 'pending') === 'approved' && empty($data['approved_at'])) {
            $data['approved_by'] = auth()->id();
            $data['approved_at'] = now();
        }

        return $data;
    }
}
