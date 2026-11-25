<?php

namespace App\Services\Payroll;

use App\Services\CompanyFormService;
use App\Services\OperationsAuditService;
use App\Enums\DeductionBasis;
use App\Models\Company;
use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaudiPayrollPolicyService
{
    public const DEFAULT_DAYS_SETTING_KEY = 'saudi_payroll_default_days';

    public const DEFAULT_CALCULATION_DAYS = 26;

    public const AUDIT_ACTION_GLOBAL_DAYS = 'saudi_payroll_default_days_updated';

    public const AUDIT_ACTION_COMPANY_POLICY = 'saudi_payroll_company_policy_updated';

    public static function getGlobalDefaultDays(): int
    {
        $raw = Setting::get(self::DEFAULT_DAYS_SETTING_KEY, self::DEFAULT_CALCULATION_DAYS);
        $days = is_numeric($raw) ? (int) $raw : self::DEFAULT_CALCULATION_DAYS;

        return ($days >= 1 && $days <= 31) ? $days : self::DEFAULT_CALCULATION_DAYS;
    }

    public static function canEditGlobalDefaultDays(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->can('manage_settings') || $user->can('update_setting');
    }

    public static function canManageCompanyPolicy(?User $user, Company $company): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! ($user->isOwner() || $user->can('manage_settings') || $user->can('update_setting') || $user->can('update_payroll_record'))) {
            return false;
        }

        $authorizedIds = array_map('intval', CompanyFormService::getAuthorizedCompanyIds($user));

        return in_array((int) $company->id, $authorizedIds, true);
    }

    public static function setGlobalDefaultDays(int $days, ?User $actor = null): int
    {
        if ($actor && ! self::canEditGlobalDefaultDays($actor)) {
            throw new AuthorizationException(__('settings.manage.payroll_attendance.errors.unauthorized_global_days'));
        }

        if ($days < 1 || $days > 31) {
            throw ValidationException::withMessages([
                'saudi_payroll_default_days' => __('settings.manage.payroll_attendance.errors.invalid_days'),
            ]);
        }

        return DB::transaction(function () use ($days, $actor): int {
            $oldDays = self::getGlobalDefaultDays();

            Setting::updateOrCreate(
                ['key' => self::DEFAULT_DAYS_SETTING_KEY],
                [
                    'label' => __('settings.manage.payroll_attendance.default_days'),
                    'group' => 'hr',
                    'input_type' => 'number',
                    'value' => (string) $days,
                    'is_active' => true,
                ]
            );

            $firstCompanyId = Company::query()->orderBy('id')->value('id');
            if ($oldDays !== $days && $firstCompanyId) {
                OperationsAuditService::record(
                    companyId: (int) $firstCompanyId,
                    transactionId: null,
                    actionType: self::AUDIT_ACTION_GLOBAL_DAYS,
                    oldStatus: (string) $oldDays,
                    newStatus: (string) $days,
                    payload: [
                        'old_days' => $oldDays,
                        'new_days' => $days,
                    ],
                    userId: $actor?->id ?? auth()->id(),
                );
            }

            return $days;
        });
    }

    /**
     * @return array{enabled: bool, effective_month: ?string, policy_version: int, monthly_days_overrides: array<string, int>, updated_by: ?int, updated_at: ?string}
     */
    public static function getCompanyPolicy(?Company $company): array
    {
        $raw = is_array($company?->metadata['payroll_policy'] ?? null)
            ? $company->metadata['payroll_policy']
            : [];

        $overrides = [];
        foreach ((array) ($raw['monthly_days_overrides'] ?? []) as $ym => $days) {
            if (preg_match('/^\d{4}-\d{2}$/', (string) $ym) && is_numeric($days) && (int) $days >= 1 && (int) $days <= 31) {
                $overrides[(string) $ym] = (int) $days;
            }
        }

        return [
            'enabled' => (bool) ($raw['enabled'] ?? false),
            'effective_month' => isset($raw['effective_month']) && preg_match('/^\d{4}-\d{2}$/', (string) $raw['effective_month'])
                ? (string) $raw['effective_month']
                : null,
            'policy_version' => max(1, (int) ($raw['policy_version'] ?? 1)),
            'monthly_days_overrides' => $overrides,
            'updated_by' => isset($raw['updated_by']) ? (int) $raw['updated_by'] : null,
            'updated_at' => isset($raw['updated_at']) ? (string) $raw['updated_at'] : null,
        ];
    }

    /**
     * @return array{enabled: bool, effective_month: ?string, policy_version: int, monthly_days_overrides: array<string, int>, updated_by: ?int, updated_at: ?string}
     */
    public static function saveCompanyPolicy(
        Company $company,
        bool $enabled,
        ?string $effectiveMonth,
        ?User $actor = null,
        ?string $overrideMonth = null,
        ?int $overrideDays = null,
    ): array {
        if ($actor && ! self::canManageCompanyPolicy($actor, $company)) {
            throw new AuthorizationException(__('settings.manage.payroll_attendance.errors.unauthorized_company_policy'));
        }

        $normalizedMonth = $effectiveMonth !== null && trim($effectiveMonth) !== '' ? trim($effectiveMonth) : null;
        if ($enabled && ($normalizedMonth === null || ! preg_match('/^\d{4}-\d{2}$/', $normalizedMonth))) {
            throw ValidationException::withMessages([
                'saudi_policy_effective_month' => __('settings.manage.payroll_attendance.errors.effective_month_required'),
            ]);
        }

        $normalizedOverrideMonth = $overrideMonth !== null && trim($overrideMonth) !== '' ? trim($overrideMonth) : null;
        if ($normalizedOverrideMonth !== null && ! preg_match('/^\d{4}-\d{2}$/', $normalizedOverrideMonth)) {
            throw ValidationException::withMessages([
                'saudi_policy_override_month' => __('settings.manage.payroll_attendance.errors.invalid_month_format'),
            ]);
        }

        if ($overrideDays !== null && ($overrideDays < 1 || $overrideDays > 31)) {
            throw ValidationException::withMessages([
                'saudi_policy_override_days' => __('settings.manage.payroll_attendance.errors.invalid_days'),
            ]);
        }

        return DB::transaction(function () use ($company, $enabled, $normalizedMonth, $actor, $normalizedOverrideMonth, $overrideDays): array {
            $lockedCompany = Company::query()->whereKey($company->id)->lockForUpdate()->firstOrFail();
            $before = self::getCompanyPolicy($lockedCompany);

            $overrides = $before['monthly_days_overrides'];
            if ($normalizedOverrideMonth !== null) {
                if ($overrideDays === null) {
                    unset($overrides[$normalizedOverrideMonth]);
                } else {
                    $overrides[$normalizedOverrideMonth] = $overrideDays;
                }
            }

            $actorId = $actor?->id ?? auth()->id();
            $next = [
                'enabled' => $enabled,
                'effective_month' => $normalizedMonth,
                'policy_version' => $before['policy_version'] + 1,
                'monthly_days_overrides' => $overrides,
                'updated_by' => $actorId,
                'updated_at' => now()->toIso8601String(),
            ];

            $metadata = is_array($lockedCompany->metadata) ? $lockedCompany->metadata : [];
            $metadata['payroll_policy'] = $next;
            $lockedCompany->metadata = $metadata;
            $lockedCompany->save();

            OperationsAuditService::recordFor(
                model: $lockedCompany,
                actionType: self::AUDIT_ACTION_COMPANY_POLICY,
                oldStatus: $before['enabled'] ? 'enabled' : 'disabled',
                newStatus: $next['enabled'] ? 'enabled' : 'disabled',
                payload: [
                    'before' => $before,
                    'after' => $next,
                ],
                userId: $actorId,
                companyId: (int) $lockedCompany->id,
            );

            return $next;
        });
    }

    /**
     * Single server-side policy resolver for a given Employee and payroll month.
     * Preserves existing record's policy version/legacy mode unless explicitRecalculate is true.
     *
     * @return array<string, mixed>
     */
    public static function resolveForPayrollMonth(
        Employee $employee,
        string $monthStr,
        ?PayrollRecord $existingRecord = null,
        bool $explicitRecalculate = false,
    ): array {
        $monthYm = Carbon::parse($monthStr)->format('Y-m');

        if (! EmployeeSalaryProfileService::isEligibleSaudiEmployee($employee)) {
            return [
                'use_new_policy' => false,
                'blocked' => false,
                'reason' => 'not_eligible_saudi',
                'month' => $monthYm,
            ];
        }

        $company = $employee->relationLoaded('company') ? $employee->company : $employee->company()->first();
        $policy = self::getCompanyPolicy($company);

        $existingSnapshot = ($existingRecord && $existingRecord->exists && is_array($existingRecord->metadata['calculation_snapshot'] ?? null))
            ? $existingRecord->metadata['calculation_snapshot']
            : null;

        $usePinnedSnapshot = $existingRecord && $existingRecord->exists && ! $explicitRecalculate && ! empty($existingSnapshot['use_new_policy']);

        if ($existingRecord && $existingRecord->exists && ! $explicitRecalculate) {
            if (empty($existingSnapshot['use_new_policy'])) {
                return [
                    'use_new_policy' => false,
                    'blocked' => false,
                    'reason' => 'existing_draft_requires_explicit_recalculation',
                    'month' => $monthYm,
                    'policy_version' => $policy['policy_version'],
                    'effective_month' => $policy['effective_month'],
                ];
            }
        }

        $dailyWorkHours = $usePinnedSnapshot
            ? (float) ($existingSnapshot['daily_work_hours'] ?? $employee->daily_work_hours ?? 0)
            : ($employee->daily_work_hours !== null ? (float) $employee->daily_work_hours : 0.0);

        if ($dailyWorkHours <= 0) {
            return [
                'use_new_policy' => false,
                'blocked' => true,
                'reason' => 'missing_daily_work_hours',
                'month' => $monthYm,
                'policy_version' => $policy['policy_version'],
                'effective_month' => $policy['effective_month'],
            ];
        }

        if ($usePinnedSnapshot) {
            $calculationDays = (int) ($existingSnapshot['calculation_days'] ?? self::getGlobalDefaultDays());
            $daysSource = (string) ($existingSnapshot['days_source'] ?? 'pinned_snapshot');
            $pinnedVersion = (int) ($existingSnapshot['policy_version'] ?? $policy['policy_version']);
            $pinnedEffectiveMonth = (string) ($existingSnapshot['effective_month'] ?? $policy['effective_month']);
            $basic = round((float) ($existingSnapshot['basic_salary'] ?? $employee->basic_salary ?? 0), 2);
            $allowances = round((float) ($existingSnapshot['allowances_amount'] ?? $employee->allowances_amount ?? 0), 2);
            $bonuses = round((float) ($existingSnapshot['bonuses_amount'] ?? $employee->bonuses_amount ?? 0), 2);
            $otherAllowances = round((float) ($existingSnapshot['other_allowances_amount'] ?? $employee->other_allowances_amount ?? 0), 2);
            $basis = DeductionBasis::tryFrom((string) ($existingSnapshot['deduction_basis'] ?? DeductionBasis::BASIC->value)) ?? DeductionBasis::BASIC;
            $overtimeRate = array_key_exists('overtime_hourly_rate', $existingSnapshot)
                ? ($existingSnapshot['overtime_hourly_rate'] !== null ? (float) $existingSnapshot['overtime_hourly_rate'] : null)
                : ($employee->overtime_hourly_rate !== null ? (float) $employee->overtime_hourly_rate : null);
            $compensationRate = array_key_exists('compensation_hourly_rate', $existingSnapshot)
                ? ($existingSnapshot['compensation_hourly_rate'] !== null ? (float) $existingSnapshot['compensation_hourly_rate'] : null)
                : ($employee->compensation_hourly_rate !== null ? (float) $employee->compensation_hourly_rate : null);
        } else {
            if (isset($policy['monthly_days_overrides'][$monthYm])) {
                $calculationDays = (int) $policy['monthly_days_overrides'][$monthYm];
                $daysSource = 'manual_month_override';
            } else {
                $calculationDays = self::getGlobalDefaultDays();
                $daysSource = 'global_default';
            }
            $pinnedVersion = $policy['policy_version'];
            $pinnedEffectiveMonth = $policy['effective_month'];
            $basic = round((float) ($employee->basic_salary ?? 0), 2);
            $allowances = round((float) ($employee->allowances_amount ?? 0), 2);
            $bonuses = round((float) ($employee->bonuses_amount ?? 0), 2);
            $otherAllowances = round((float) ($employee->other_allowances_amount ?? 0), 2);
            $basis = $employee->deduction_basis instanceof DeductionBasis
                ? $employee->deduction_basis
                : (DeductionBasis::tryFrom((string) ($employee->deduction_basis ?? DeductionBasis::BASIC->value)) ?? DeductionBasis::BASIC);
            $overtimeRate = $employee->overtime_hourly_rate !== null ? (float) $employee->overtime_hourly_rate : null;
            $compensationRate = $employee->compensation_hourly_rate !== null ? (float) $employee->compensation_hourly_rate : null;
        }

        $totalSalary = round($basic + $allowances + $bonuses + $otherAllowances, 2);

        $deductionBaseAmount = match ($basis) {
            DeductionBasis::BASIC => $basic,
            DeductionBasis::BASIC_ALLOWANCES => round($basic + $allowances, 2),
            DeductionBasis::BASIC_BONUSES => round($basic + $bonuses, 2),
            DeductionBasis::TOTAL => $totalSalary,
        };

        $dayRate = $deductionBaseAmount / $calculationDays;
        $hourRate = $dayRate / $dailyWorkHours;

        return [
            'use_new_policy' => true,
            'blocked' => false,
            'reason' => 'active',
            'month' => $monthYm,
            'policy_version' => $pinnedVersion,
            'effective_month' => $pinnedEffectiveMonth,
            'calculation_days' => $calculationDays,
            'days_source' => $daysSource,
            'daily_work_hours' => $dailyWorkHours,
            'deduction_basis' => $basis->value,
            'deduction_base_amount' => round($deductionBaseAmount, 2),
            'day_rate' => $dayRate,
            'hour_rate' => $hourRate,
            'basic_salary' => $basic,
            'allowances_amount' => $allowances,
            'bonuses_amount' => $bonuses,
            'other_allowances_amount' => $otherAllowances,
            'total_salary' => $totalSalary,
            'overtime_hourly_rate' => $overtimeRate,
            'compensation_hourly_rate' => $compensationRate,
        ];
    }
}
