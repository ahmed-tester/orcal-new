<?php

namespace App\Observers;

use App\Models\Employee;
use App\Models\SupervisionShift;
use App\Services\EmployeePerformanceAccessService;
use Illuminate\Validation\ValidationException;

class SupervisionShiftObserver
{
    public function saving(SupervisionShift $shift): void
    {
        if ($shift->supervisor_id && (! $shift->exists || $shift->isDirty('supervisor_id'))) {
            $supervisor = Employee::with('user')->find($shift->supervisor_id);

            if (! EmployeePerformanceAccessService::isEligibleSupervisorCandidate($supervisor)) {
                throw ValidationException::withMessages([
                    'supervisor_id' => __('tasks.validation.supervisor_ineligible'),
                ]);
            }
        }

        if (is_array($shift->assigned_employees) && $shift->assigned_employees !== [] && (! $shift->exists || $shift->isDirty('assigned_employees'))) {
            $memberIds = array_values(array_unique(array_filter(array_map('intval', $shift->assigned_employees))));
            $members = Employee::with('user')->whereIn('id', $memberIds)->get()->keyBy('id');

            foreach ($memberIds as $memberId) {
                if (! EmployeePerformanceAccessService::isEligibleSaudiTaskEmployee($members->get($memberId))) {
                    throw ValidationException::withMessages([
                        'assigned_employees' => __('tasks.validation.saudi_only'),
                    ]);
                }
            }
        }
    }

    public function saved(SupervisionShift $shift): void
    {
        $this->markWasTaskSupervisor($shift);
    }

    public function deleting(SupervisionShift $shift): void
    {
        $this->markWasTaskSupervisor($shift);
    }

    private function markWasTaskSupervisor(SupervisionShift $shift): void
    {
        if (! $shift->supervisor_id) {
            return;
        }

        $employee = Employee::find($shift->supervisor_id);
        if (! $employee) {
            return;
        }

        $meta = is_array($employee->metadata) ? $employee->metadata : [];
        if (empty($meta['was_task_supervisor'])) {
            $meta['was_task_supervisor'] = true;
            $employee->forceFill(['metadata' => $meta])->saveQuietly();
        }
    }
}
