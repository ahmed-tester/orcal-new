<?php

namespace App\Services\Payroll;

use App\Services\OperationsAuditService;
use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Models\UnifiedAuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaudiCompensationGrantService
{
    public const RECURRENCE_ONCE = 'once';

    public const RECURRENCE_DAILY = 'daily';

    public const RECURRENCE_MONTHLY = 'monthly';

    public const AUDIT_ACTION_GRANT_CREATED = 'saudi_compensation_grant_created';

    public const AUDIT_ACTION_GRANT_REVOKED = 'saudi_compensation_grant_revoked';

    /**
     * @return list<string>
     */
    public static function recurrences(): array
    {
        return [self::RECURRENCE_ONCE, self::RECURRENCE_DAILY, self::RECURRENCE_MONTHLY];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getOperationalGrants(Employee $employee): array
    {
        $grants = $employee->metadata['compensation_grants'] ?? [];

        return is_array($grants) ? array_values($grants) : [];
    }

    /**
     * Create a new compensation grant on employees.metadata.compensation_grants with audit trail.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function createGrant(User $actor, Employee $employee, array $input): array
    {
        if (! EmployeeCompensationEligibilityService::canManage($actor, $employee)) {
            throw new AuthorizationException(__('employees.compensation_eligibility.errors.unauthorized'));
        }

        $recurrence = (string) ($input['recurrence'] ?? '');
        if (! in_array($recurrence, self::recurrences(), true)) {
            throw ValidationException::withMessages([
                'recurrence' => __('employees.compensation_grants.errors.invalid_recurrence'),
            ]);
        }

        $minutes = isset($input['minutes']) && is_numeric($input['minutes']) ? (int) $input['minutes'] : 0;
        if ($minutes <= 0) {
            throw ValidationException::withMessages([
                'minutes' => __('employees.compensation_grants.errors.invalid_minutes'),
            ]);
        }

        if (empty($input['starts_on'])) {
            throw ValidationException::withMessages([
                'starts_on' => __('employees.compensation_grants.errors.starts_on_required'),
            ]);
        }

        $startsOn = Carbon::parse($input['starts_on'])->toDateString();
        $endsOn = ! empty($input['ends_on']) ? Carbon::parse($input['ends_on'])->toDateString() : null;

        if ($recurrence === self::RECURRENCE_ONCE && $endsOn === null) {
            $endsOn = $startsOn;
        }

        if ($endsOn !== null && $endsOn < $startsOn) {
            throw ValidationException::withMessages([
                'ends_on' => __('employees.compensation_grants.errors.ends_before_start'),
            ]);
        }

        $reason = trim((string) ($input['reason'] ?? ''));
        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => __('employees.compensation_eligibility.errors.reason_required'),
            ]);
        }

        return DB::transaction(function () use ($actor, $employee, $recurrence, $minutes, $startsOn, $endsOn, $reason): array {
            $lockedEmployee = Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            if (empty($lockedEmployee->company_id)) {
                throw ValidationException::withMessages([
                    'company_id' => __('employees.salary_profile.errors.missing_company'),
                ]);
            }

            $grantId = (string) Str::uuid();
            $grant = [
                'grant_id' => $grantId,
                'revision' => 1,
                'recurrence' => $recurrence,
                'minutes' => $minutes,
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'status' => 'active',
                'reason' => $reason,
                'created_by' => $actor->id,
                'created_at' => now()->toIso8601String(),
                'last_audit_id' => null,
            ];

            /** @var UnifiedAuditLog $auditLog */
            $auditLog = OperationsAuditService::recordFor(
                model: $lockedEmployee,
                actionType: self::AUDIT_ACTION_GRANT_CREATED,
                oldStatus: null,
                newStatus: 'active',
                payload: [
                    'grant' => $grant,
                    'reason' => $reason,
                ],
                userId: $actor->id,
                companyId: (int) $lockedEmployee->company_id,
            );

            $grant['last_audit_id'] = $auditLog->id;

            $meta = is_array($lockedEmployee->metadata) ? $lockedEmployee->metadata : [];
            $grants = self::getOperationalGrants($lockedEmployee);
            $grants[] = $grant;
            $meta['compensation_grants'] = $grants;
            $lockedEmployee->metadata = $meta;
            $lockedEmployee->save();

            return $grant;
        });
    }

    /**
     * Revoke an existing grant for future dates without altering prior confirmed payrolls.
     *
     * @return array<string, mixed>
     */
    public static function revokeGrant(
        User $actor,
        Employee $employee,
        string $grantId,
        string $effectiveDate,
        string $reason,
    ): array {
        if (! EmployeeCompensationEligibilityService::canManage($actor, $employee)) {
            throw new AuthorizationException(__('employees.compensation_eligibility.errors.unauthorized'));
        }

        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw ValidationException::withMessages([
                'reason' => __('employees.compensation_eligibility.errors.reason_required'),
            ]);
        }

        $parsedDate = Carbon::parse($effectiveDate)->toDateString();

        return DB::transaction(function () use ($actor, $employee, $grantId, $parsedDate, $trimmedReason): array {
            $lockedEmployee = Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            if (empty($lockedEmployee->company_id)) {
                throw ValidationException::withMessages([
                    'company_id' => __('employees.salary_profile.errors.missing_company'),
                ]);
            }

            $grants = self::getOperationalGrants($lockedEmployee);
            $foundIndex = null;
            foreach ($grants as $idx => $g) {
                if (($g['grant_id'] ?? '') === $grantId) {
                    $foundIndex = $idx;
                    break;
                }
            }

            if ($foundIndex === null) {
                throw ValidationException::withMessages([
                    'grant_id' => __('employees.compensation_grants.errors.grant_not_found'),
                ]);
            }

            $before = $grants[$foundIndex];
            $after = $before;
            $after['revision'] = ((int) ($before['revision'] ?? 1)) + 1;
            $after['status'] = 'revoked';
            $after['revoked_from'] = $parsedDate;
            $after['ends_on'] = $before['ends_on'] !== null
                ? min((string) $before['ends_on'], $parsedDate)
                : $parsedDate;
            $after['revoked_reason'] = $trimmedReason;

            $auditLog = OperationsAuditService::recordFor(
                model: $lockedEmployee,
                actionType: self::AUDIT_ACTION_GRANT_REVOKED,
                oldStatus: (string) ($before['status'] ?? 'active'),
                newStatus: 'revoked',
                payload: [
                    'grant_id' => $grantId,
                    'before' => $before,
                    'after' => $after,
                    'effective_date' => $parsedDate,
                    'reason' => $trimmedReason,
                ],
                userId: $actor->id,
                companyId: (int) $lockedEmployee->company_id,
            );

            $after['last_audit_id'] = $auditLog->id;
            $grants[$foundIndex] = $after;

            $meta = is_array($lockedEmployee->metadata) ? $lockedEmployee->metadata : [];
            $meta['compensation_grants'] = $grants;
            $lockedEmployee->metadata = $meta;
            $lockedEmployee->save();

            return $after;
        });
    }

    /**
     * Compute late-arrival deductions and compensation allocations for a month under the new policy.
     * Enforces ceiling (= remaining uncompensated late deduction) and zero carryover.
     *
     * @param  array<string, mixed>  $resolvedPolicy
     * @param  array<string, float>  $sharedCycleUsage
     * @return array<string, mixed>
     */
    public static function calculateMonthlyLateAndCompensation(
        Employee $employee,
        string $monthStr,
        array $resolvedPolicy,
        ?int $currentPayrollRecordId = null,
        ?string $requestKey = null,
        ?string $filterStartDate = null,
        ?string $filterEndDate = null,
        array &$sharedCycleUsage = [],
    ): array {
        $monthStart = Carbon::parse($monthStr)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $monthYm = $monthStart->format('Y-m');
        $hourRate = (float) ($resolvedPolicy['hour_rate'] ?? 0);

        $periodStart = $filterStartDate !== null
            ? max($monthStart->toDateString(), Carbon::parse($filterStartDate)->toDateString())
            : $monthStart->toDateString();
        $periodEnd = $filterEndDate !== null
            ? min($monthEnd->toDateString(), Carbon::parse($filterEndDate)->toDateString())
            : $monthEnd->toDateString();
        $isFiltered = $filterStartDate !== null || $filterEndDate !== null;

        $attendances = ($isFiltered && $periodStart > $periodEnd)
            ? collect()
            : Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('date', '>=', $monthStart->toDateString())
                ->whereDate('date', '<=', $monthEnd->toDateString())
                ->where('late_hours', '>', 0)
                ->where('status', '!=', AttendanceStatus::ABSENT)
                ->orderBy('date')
                ->orderBy('id')
                ->get();

        $lateIncidents = [];
        $eligibilityByDate = [];
        foreach ($attendances as $att) {
            $mins = (int) ($att->late_hours ?? 0);
            if ($mins <= 0) {
                continue;
            }
            $dateStr = $att->date->toDateString();
            if (! array_key_exists($dateStr, $eligibilityByDate)) {
                $eligibilityByDate[$dateStr] = EmployeeCompensationEligibilityService::isCurrentlyEligible($employee, $dateStr);
            }
            $lateIncidents[] = [
                'attendance_id' => $att->id,
                'date' => $dateStr,
                'late_minutes' => $mins,
            ];
        }

        $grants = self::getOperationalGrants($employee);
        $compRate = array_key_exists('compensation_hourly_rate', $resolvedPolicy)
            ? ($resolvedPolicy['compensation_hourly_rate'] !== null ? (float) $resolvedPolicy['compensation_hourly_rate'] : null)
            : ($employee->compensation_hourly_rate !== null ? (float) $employee->compensation_hourly_rate : null);

        $otherAllocationsByCycle = (! empty($lateIncidents) && ! empty($grants) && $compRate !== null && $compRate > 0)
            ? self::loadAllocatedMinutesByCycle($employee->id, $currentPayrollRecordId)
            : [];

        $result = PayrollEntitlementCalculator::calculateLateAndCompensation([
            'month' => $monthYm,
            'hour_rate' => $hourRate,
            'compensation_hourly_rate' => $compRate,
            'late_incidents' => $lateIncidents,
            'grants' => $grants,
            'eligibility_by_date' => $eligibilityByDate,
            'other_allocations_by_cycle' => $otherAllocationsByCycle,
            'shared_cycle_usage' => $sharedCycleUsage,
            'request_key' => $requestKey,
            'filter_start_date' => $filterStartDate,
            'filter_end_date' => $filterEndDate,
        ]);

        if (is_array($result['shared_once_usage'] ?? null)) {
            $sharedCycleUsage = $result['shared_once_usage'];
        }
        unset($result['shared_once_usage']);

        return $result;
    }

    /**
     * Confirm reserved allocations when payroll is approved/disbursed.
     */
    public static function confirmAllocationsForRecord(PayrollRecord $record): void
    {
        $meta = is_array($record->metadata) ? $record->metadata : [];
        $allocations = $meta['compensation_allocations'] ?? null;
        if (! is_array($allocations) || empty($allocations)) {
            return;
        }

        $changed = false;
        foreach ($allocations as &$item) {
            if (($item['status'] ?? '') === 'reserved') {
                $item['status'] = 'confirmed';
                $item['confirmed_at'] = now()->toIso8601String();
                $changed = true;
            }
        }
        unset($item);

        if ($changed) {
            $meta['compensation_allocations'] = $allocations;
            $record->metadata = $meta;
            $record->save();
        }
    }

    /**
     * Reverse allocations on payroll reversal/deletion (does not revive expired cycles).
     */
    public static function reverseAllocationsForRecord(PayrollRecord $record, string $reason = ''): void
    {
        $meta = is_array($record->metadata) ? $record->metadata : [];
        $allocations = $meta['compensation_allocations'] ?? null;
        if (! is_array($allocations) || empty($allocations)) {
            return;
        }

        $changed = false;
        foreach ($allocations as &$item) {
            if (in_array(($item['status'] ?? ''), ['reserved', 'confirmed'], true)) {
                $item['status'] = 'reversed';
                $item['reversed_at'] = now()->toIso8601String();
                if ($reason !== '') {
                    $item['reverse_reason'] = $reason;
                }
                $changed = true;
            }
        }
        unset($item);

        if ($changed) {
            $meta['compensation_allocations'] = $allocations;
            $record->metadata = $meta;
            $record->save();
        }
    }

    /**
     * @return array<string, float>
     */
    private static function loadAllocatedMinutesByCycle(int $employeeId, ?int $excludeRecordId): array
    {
        $records = PayrollRecord::query()
            ->where('employee_id', $employeeId)
            ->where('operation_type', 'payroll')
            ->when($excludeRecordId !== null, fn ($q) => $q->where('id', '!=', $excludeRecordId))
            ->get(['id', 'metadata']);

        $totals = [];
        foreach ($records as $rec) {
            $allocs = $rec->metadata['compensation_allocations'] ?? [];
            if (! is_array($allocs)) {
                continue;
            }
            foreach ($allocs as $alloc) {
                if (! in_array(($alloc['status'] ?? ''), ['reserved', 'confirmed'], true)) {
                    continue;
                }
                $key = (string) ($alloc['cycle_key'] ?? '');
                if ($key === '') {
                    continue;
                }
                $totals[$key] = ($totals[$key] ?? 0.0) + (float) ($alloc['consumed_minutes'] ?? 0);
            }
        }

        return $totals;
    }

}
