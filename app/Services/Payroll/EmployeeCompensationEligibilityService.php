<?php

namespace App\Services\Payroll;

use App\Services\CompanyFormService;
use App\Services\OperationsAuditService;
use App\Models\Employee;
use App\Models\UnifiedAuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeCompensationEligibilityService
{
    public const PERMISSION = 'manage_employee_compensation';

    public const AUDIT_ACTION = 'saudi_compensation_eligibility_changed';

    public static function canManage(?User $user, Employee $employee): bool
    {
        if (! $user) {
            return false;
        }

        if (! EmployeeSalaryProfileService::isEligibleSaudiEmployee($employee)) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! $user->can(self::PERMISSION)) {
            return false;
        }

        $authorizedCompanyIds = CompanyFormService::getAuthorizedCompanyIds($user);
        if (empty($authorizedCompanyIds)) {
            return false;
        }

        if (empty($employee->company_id) || ! in_array((int) $employee->company_id, array_map('intval', $authorizedCompanyIds), true)) {
            return false;
        }

        if ($user->branch_id && ! $user->canAccessAllBranches()) {
            return (int) $employee->branch_id === (int) $user->branch_id;
        }

        return true;
    }

    /**
     * Evaluate effective eligibility as of a specific date from UnifiedAuditLog,
     * ordering by effective_date DESC then event id DESC.
     */
    public static function isCurrentlyEligible(Employee $employee, ?string $asOfDate = null): bool
    {
        $decision = self::resolveEffectiveDecisionAsOf($employee, $asOfDate);

        if ($decision !== null) {
            return (bool) $decision['is_eligible'];
        }

        if ($asOfDate === null && $employee->compensation_is_eligible) {
            return true;
        }

        return false;
    }

    /**
     * @return array{is_eligible: bool, effective_date: string, event_id: int}|null
     */
    public static function resolveEffectiveDecisionAsOf(Employee $employee, ?string $asOfDate = null): ?array
    {
        $targetDate = $asOfDate ? Carbon::parse($asOfDate)->toDateString() : now()->toDateString();

        $events = UnifiedAuditLog::query()
            ->where('auditable_type', Employee::class)
            ->where('auditable_id', $employee->id)
            ->where('action_type', self::AUDIT_ACTION)
            ->get(['id', 'payload']);

        $effective = $events
            ->map(function (UnifiedAuditLog $log): ?array {
                $payload = is_array($log->payload) ? $log->payload : [];
                $effDate = isset($payload['effective_date']) ? (string) $payload['effective_date'] : null;
                if (! $effDate) {
                    return null;
                }

                return [
                    'event_id' => (int) $log->id,
                    'effective_date' => $effDate,
                    'is_eligible' => (bool) ($payload['is_eligible'] ?? false),
                ];
            })
            ->filter(fn (?array $row) => $row !== null && $row['effective_date'] <= $targetDate)
            ->sort(function (array $a, array $b): int {
                $dateCmp = strcmp($b['effective_date'], $a['effective_date']);
                if ($dateCmp !== 0) {
                    return $dateCmp;
                }

                return $b['event_id'] <=> $a['event_id'];
            })
            ->first();

        return $effective ?: null;
    }

    /**
     * Append an eligibility decision (grant or revoke) to UnifiedAuditLog and sync Employee's current effective state.
     *
     * @param  array<string, mixed>  $extra
     */
    public static function setEligibility(
        User $actor,
        Employee $employee,
        bool $isEligible,
        string $effectiveDate,
        string $reason,
        array $extra = [],
    ): UnifiedAuditLog {
        if (array_key_exists('compensation_minutes', $extra) && $extra['compensation_minutes'] !== null && $extra['compensation_minutes'] !== '') {
            throw ValidationException::withMessages([
                'compensation_minutes' => __('employees.salary_profile.errors.compensation_minutes_unsupported'),
            ]);
        }

        if (! self::canManage($actor, $employee)) {
            throw new AuthorizationException(__('employees.compensation_eligibility.errors.unauthorized'));
        }

        $trimmedReason = trim($reason);
        if ($trimmedReason === '') {
            throw ValidationException::withMessages([
                'reason' => __('employees.compensation_eligibility.errors.reason_required'),
            ]);
        }

        $parsedDate = Carbon::parse($effectiveDate)->toDateString();

        return DB::transaction(function () use ($actor, $employee, $isEligible, $parsedDate, $trimmedReason): UnifiedAuditLog {
            $lockedEmployee = Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            if (empty($lockedEmployee->company_id)) {
                throw ValidationException::withMessages([
                    'company_id' => __('employees.salary_profile.errors.missing_company'),
                ]);
            }

            $previousEffective = self::resolveEffectiveDecisionAsOf($lockedEmployee);

            $auditLog = OperationsAuditService::recordFor(
                model: $lockedEmployee,
                actionType: self::AUDIT_ACTION,
                oldStatus: ($previousEffective['is_eligible'] ?? false) ? 'eligible' : 'not_eligible',
                newStatus: $isEligible ? 'eligible' : 'not_eligible',
                payload: [
                    'is_eligible' => $isEligible,
                    'effective_date' => $parsedDate,
                    'reason' => $trimmedReason,
                    'source' => 'saudi_employee_action',
                ],
                userId: $actor->id,
                companyId: (int) $lockedEmployee->company_id,
            );

            $currentEffective = self::resolveEffectiveDecisionAsOf($lockedEmployee, now()->toDateString());
            $lockedEmployee->compensation_is_eligible = (bool) ($currentEffective['is_eligible'] ?? false);
            $lockedEmployee->compensation_effective_date = $currentEffective['effective_date'] ?? null;
            $lockedEmployee->save();

            return $auditLog;
        });
    }
}
