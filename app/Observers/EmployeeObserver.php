<?php

namespace App\Observers;

use App\Models\Employee;
use App\Services\EmployeeServiceDurationService;
use App\Services\PersonCapacityService;

class EmployeeObserver
{
    public function creating(Employee $employee): void
    {
        if (empty($employee->employee_number)) {
            $employee->employee_number = Employee::generateNextEmployeeNumber($employee->company_id);
        }
    }

    public function saving(Employee $employee): void
    {
        $employee->basic_salary = (float) ($employee->basic_salary ?? 0);

        $employee->contract_duration = $employee->contract_duration
            ?: EmployeeServiceDurationService::calculateContractDuration(
                $employee->hire_date,
                $employee->contract_end_date
            );

        // Persist duration metrics in metadata JSON column without touching unrelated keys
        $meta = is_array($employee->metadata)
            ? $employee->metadata
            : (is_string($employee->metadata) ? (json_decode($employee->metadata, true) ?? []) : []);

        $days = EmployeeServiceDurationService::calculateDaysServed(
            $employee->hire_date,
            $employee->termination_date ?? $employee->contract_end_date
        );

        $computed = [
            'days_served' => $days,
            'days_served_formatted' => $days !== null ? EmployeeServiceDurationService::formatDaysServed($days) : null,
            'time_since_termination' => EmployeeServiceDurationService::calculateTimeSinceTermination($employee->termination_date),
        ];

        $employee->metadata = array_merge(
            array_diff_key($meta, $computed),
            array_filter($computed, fn ($value) => $value !== null)
        );
    }

    public function deleted(Employee $employee): void
    {
        PersonCapacityService::cleanupOrphanedUser($employee->user_id, 'employee', $employee->id);
    }
}
