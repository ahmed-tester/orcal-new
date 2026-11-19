<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeDetail;
use Illuminate\Validation\ValidationException;

class EmployeeIdentityService
{
    public static function ensureAvailable(
        string $field,
        ?string $number,
        ?Employee $employee = null,
        ?int $sharedUserId = null,
    ): void {
        if (blank($number) || $number === $employee?->details?->{$field}) {
            return;
        }

        $conflict = EmployeeDetail::query()
            ->where($field, $number)
            ->when($employee, fn ($query) => $query->where('employee_id', '!=', $employee->getKey()))
            ->when($sharedUserId, fn ($query) => $query->whereDoesntHave(
                'employee',
                fn ($employees) => $employees->where('user_id', $sharedUserId),
            ))
            ->exists();

        if ($conflict) {
            throw ValidationException::withMessages([
                "data.{$field}" => __($field === 'national_id'
                    ? 'employees.fields.errors.national_id_exists'
                    : 'employees.fields.errors.iqama_exists'),
            ]);
        }
    }
}
