<?php

namespace App\Services;

use App\Enums\AnnualLeaveType;
use App\Enums\LeaveStatus;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;

class LeaveBalanceService
{
    /**
     * Check if the current or given user has manager authority to override leave balances.
     */
    public static function canUserOverrideLeaveBalance(?User $user = null): bool
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isSuperAdminOrOwner') && $user->isSuperAdminOrOwner()) {
            return true;
        }

        if ($user->isSuperAdmin() || $user->isOwner()) {
            return true;
        }

        return $user->can('override_leave_balance');
    }

    /**
     * Get or resolve the dedicated Annual Leave Type ID using AnnualLeaveType enum.
     */
    public static function getDedicatedAnnualLeaveTypeId(): ?int
    {
        return AnnualLeaveType::DEDICATED_ANNUAL->getLeaveTypeId();
    }

    /**
     * Get options list for leave types select field:
     * - The fixed dedicated annual leave is always at the very top.
     * - Plus ALL other leave types from the database (including "سنوي", "مرضي", etc.)!
     */
    public static function getLeaveTypeOptions(): array
    {
        $dedicatedId = static::getDedicatedAnnualLeaveTypeId();
        $options = [];

        if ($dedicatedId) {
            $options[$dedicatedId] = AnnualLeaveType::DEDICATED_ANNUAL->getLabel();
        }

        $allOthers = LeaveType::query()
            ->when($dedicatedId, fn ($q) => $q->where('id', '!=', $dedicatedId))
            ->get();

        foreach ($allOthers as $lt) {
            $options[$lt->id] = $lt->name;
        }

        return $options;
    }

    /**
     * Check if the given leave type is the dedicated Annual Leave Type using AnnualLeaveType enum.
     */
    public static function isAnnualLeaveType(?int $leaveTypeId): bool
    {
        return AnnualLeaveType::DEDICATED_ANNUAL->matches($leaveTypeId);
    }

    /**
     * Calculate accrued, used, and available leave balance for an employee.
     *
     * @return array{
     *     is_annual: bool,
     *     annual_rate: int,
     *     hire_date: ?string,
     *     service_months: float,
     *     accrued_days: float,
     *     used_days: float,
     *     available_days: float,
     *     summary_text: string,
     * }
     */
    public static function calculateBalance(
        Employee|int|null $employee,
        ?int $leaveTypeId = null,
        ?int $ignoreLeaveId = null,
        Carbon|string|null $asOfDate = null
    ): array {
        if (is_numeric($employee)) {
            $employee = Employee::find($employee);
        }

        if (!$employee) {
            return [
                'is_annual' => false,
                'annual_rate' => 21,
                'hire_date' => null,
                'service_months' => 0,
                'accrued_days' => 0,
                'used_days' => 0,
                'available_days' => 0,
                'summary_text' => __('leaves.summary.not_available'),
            ];
        }

        $isAnnual = static::isAnnualLeaveType($leaveTypeId);

        // Service duration calculation based on hire_date
        $hireDate = $employee->hire_date 
            ? Carbon::parse($employee->hire_date) 
            : ($employee->created_at ? Carbon::parse($employee->created_at) : now());

        $targetDate = $asOfDate ? Carbon::parse($asOfDate) : now();
        if ($targetDate->lessThan($hireDate)) {
            $targetDate = $hireDate;
        }

        $daysWorked = max(0, $hireDate->diffInDays($targetDate));
        $yearsWorked = $daysWorked / 365.25;
        $serviceMonths = round($daysWorked / 30.4375, 1);

        if ($isAnnual) {
            // Relies DIRECTLY on the annual rate given to the employee in the employee screen!
            $annualRate = (int) ($employee->annual_leave_days ?? 21);
            if ($annualRate <= 0) {
                $annualRate = 21;
            }

            // Accrued based on service duration from hire date, rounded down to integer (floor)
            $accruedDays = (int) floor($yearsWorked * $annualRate);

            // Sum all historical approved annual leaves for this employee
            $usedQuery = Leave::query()
                ->where('employee_id', $employee->id)
                ->where('leave_type_id', $leaveTypeId)
                ->where('status', LeaveStatus::APPROVED);

            if ($ignoreLeaveId) {
                $usedQuery->where('id', '!=', $ignoreLeaveId);
            }

            $usedDays = (float) $usedQuery->sum('days');
            $availableDays = (int) max(0, floor($accruedDays - $usedDays));

            $summary = __('leaves.summary.annual_available', [
                'available' => $availableDays,
                'accrued' => $accruedDays,
                'months' => $serviceMonths,
                'rate' => $annualRate,
                'used' => $usedDays,
            ]);
        } else {
            // Other leave types (e.g. sick leave) rely on LeaveType->annual_balance
            $leaveType = $leaveTypeId ? LeaveType::find($leaveTypeId) : null;
            $annualRate = (int) ($leaveType?->annual_balance ?? 30);
            $totalQuota = (float) $annualRate;

            $usedQuery = Leave::query()
                ->where('employee_id', $employee->id)
                ->where('leave_type_id', $leaveTypeId)
                ->where('status', LeaveStatus::APPROVED)
                ->whereYear('start_date', $targetDate->year);

            if ($ignoreLeaveId) {
                $usedQuery->where('id', '!=', $ignoreLeaveId);
            }

            $usedDays = (float) $usedQuery->sum('days');
            $availableDays = max(0, $totalQuota - $usedDays);
            $accruedDays = $totalQuota;

            $typeName = $leaveType?->name ?? __('leaves.fields.leave_type');
            $summary = __('leaves.summary.other_available', [
                'name' => $typeName,
                'available' => $availableDays,
                'quota' => $totalQuota,
                'used' => $usedDays,
            ]);
        }

        return [
            'is_annual' => $isAnnual,
            'annual_rate' => $annualRate,
            'hire_date' => $hireDate->format('Y-m-d'),
            'service_months' => $serviceMonths,
            'accrued_days' => $accruedDays,
            'used_days' => $usedDays,
            'available_days' => $availableDays,
            'summary_text' => $summary,
        ];
    }
}
