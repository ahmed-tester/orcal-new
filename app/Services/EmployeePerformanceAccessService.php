<?php

namespace App\Services;

use App\Enums\UserType;
use App\Models\Employee;
use App\Models\ServiceTransaction;
use App\Models\SupervisionShift;
use App\Models\TaskAssignment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

/**
 * Server-side access decisions for the employee performance page.
 *
 * Task cards, service cards and the employee record itself have separate
 * eligibility rules. The page is available when the viewer may see the
 * employee record and at least one card; each card is then authorized again
 * on every Livewire request by the dashboard trait.
 */
class EmployeePerformanceAccessService
{
    public static function viewer(): ?User
    {
        return auth()->user();
    }

    public static function isSelf(User $user, Employee $employee): bool
    {
        $viewerEmployeeId = $user->employee?->id;

        return $viewerEmployeeId !== null
            && (int) $viewerEmployeeId === (int) $employee->id;
    }

    public static function isSupervisor(User $user): bool
    {
        return $user->user_type === UserType::SUPERVISOR;
    }

    /**
     * Employee record visibility (company/branch scope plus supervision
     * coverage when the viewer is a supervisor).
     */
    public static function canViewEmployeeRecord(
        User $user,
        Employee $employee,
        ?CarbonInterface $periodStart = null,
        ?CarbonInterface $periodEnd = null,
    ): bool {
        $inScope = CompanyAccessScopeService::apply(Employee::query()->nonTemporary())
            ->whereKey($employee->getKey())
            ->exists();

        if (! $inScope) {
            return false;
        }

        return self::supervisorCovers($user, $employee, $periodStart, $periodEnd);
    }

    /**
     * Task cards are restricted to the same Saudi employee definition as the
     * task assignment form. Foreign employees keep the services card only.
     */
    public static function canViewTaskData(
        User $user,
        Employee $employee,
        ?CarbonInterface $periodStart = null,
        ?CarbonInterface $periodEnd = null,
    ): bool {
        if (! Employee::query()->saudi()->whereKey($employee->getKey())->exists()) {
            return false;
        }

        if (self::isSelf($user, $employee)) {
            return true;
        }

        return Gate::allows('viewAny', TaskAssignment::class)
            && self::canViewEmployeeRecord($user, $employee, $periodStart, $periodEnd);
    }

    public static function canViewServiceData(
        User $user,
        Employee $employee,
        ?CarbonInterface $periodStart = null,
        ?CarbonInterface $periodEnd = null,
    ): bool {
        return Gate::allows('viewAny', ServiceTransaction::class)
            && self::canViewEmployeeRecord($user, $employee, $periodStart, $periodEnd);
    }

    public static function canViewAnyData(
        User $user,
        Employee $employee,
        ?CarbonInterface $periodStart = null,
        ?CarbonInterface $periodEnd = null,
    ): bool {
        return self::canViewTaskData($user, $employee, $periodStart, $periodEnd)
            || self::canViewServiceData($user, $employee, $periodStart, $periodEnd);
    }

    /**
     * A supervisor only sees other employees through an active/completed
     * SupervisionShift that lists the employee in the assigned team, matches
     * the employee company, and overlaps the requested period. Anything that
     * cannot be verified stays outside the supervisor scope.
     */
    public static function supervisorCovers(
        User $user,
        Employee $employee,
        ?CarbonInterface $periodStart = null,
        ?CarbonInterface $periodEnd = null,
    ): bool {
        if (! self::isSupervisor($user) || self::isSelf($user, $employee)) {
            return true;
        }

        return self::supervisorWindows($user, $employee, $periodStart, $periodEnd) !== [];
    }

    /**
     * Shift windows used to restrict a supervisor to the team and duration
     * recorded in SupervisionShift. Each item is [startDate, endDate].
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public static function supervisorWindows(
        User $user,
        Employee $employee,
        ?CarbonInterface $periodStart = null,
        ?CarbonInterface $periodEnd = null,
    ): array {
        if (! self::isSupervisor($user) || self::isSelf($user, $employee)) {
            return [];
        }

        $windows = [];

        foreach (self::teamWindows($user, $periodStart, $periodEnd) as $window) {
            if ((int) $employee->company_id === $window['company']
                && in_array((int) $employee->id, $window['employees'], true)) {
                $windows[] = [$window['start'], $window['end']];
            }
        }

        return $windows;
    }

    /**
     * @return array<int, array{company: int, employees: list<int>, start: string, end: string}>
     */
    public static function teamWindows(
        User $user,
        ?CarbonInterface $periodStart = null,
        ?CarbonInterface $periodEnd = null,
    ): array {
        $supervisor = $user->employee;

        if (! self::isSupervisor($user) || ! $supervisor) {
            return [];
        }

        $shifts = SupervisionShift::query()
            ->where('supervisor_id', $supervisor->id)
            ->whereIn('status', ['active', 'completed'])
            ->whereNotNull(['company_id', 'start_date', 'end_date'])
            ->when($periodStart, fn (Builder $query) => $query->where('end_date', '>=', $periodStart->toDateString()))
            ->when($periodEnd, fn (Builder $query) => $query->where('start_date', '<=', $periodEnd->toDateString()))
            ->get();

        $windows = [];

        foreach ($shifts as $shift) {
            $start = $shift->start_date->toDateString();
            $end = $shift->end_date->toDateString();

            if ($start > $end || empty($shift->assigned_employees)) {
                continue;
            }

            $windows[] = [
                'company' => (int) $shift->company_id,
                'employees' => array_values(array_map('intval', $shift->assigned_employees)),
                'start' => $periodStart ? max($start, $periodStart->toDateString()) : $start,
                'end' => $periodEnd ? min($end, $periodEnd->toDateString()) : $end,
            ];
        }

        return $windows;
    }
}
