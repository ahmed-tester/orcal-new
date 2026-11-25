<?php

namespace App\Services\Payroll;

use App\Services\OperationsAuditService;
use App\Enums\DeductionBasis;
use App\Enums\EmploymentType;
use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeSalaryProfileService
{
    public const AUDIT_ACTION = 'saudi_salary_settings_updated';

    public const SNAPSHOT_VERSION = '1.0';

    /**
     * @var list<string>
     */
    public const WEEK_DAYS = [
        'sunday',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
    ];

    public static function isEligibleSaudiEmployee(Employee $employee): bool
    {
        $employmentType = $employee->employment_type instanceof EmploymentType
            ? $employee->employment_type->value
            : (string) ($employee->employment_type ?? '');

        if ($employmentType === EmploymentType::TEMPORARY->value) {
            return false;
        }

        return $employee->isSaudi();
    }

    public static function calculateTotalSalary(
        mixed $basicSalary,
        mixed $allowances,
        mixed $bonuses,
        mixed $otherAllowances,
    ): float {
        $basic = self::parseNonNegativeMoney($basicSalary, 'basic_salary');
        $allow = self::parseNonNegativeMoney($allowances, 'allowances_amount');
        $bonus = self::parseNonNegativeMoney($bonuses, 'bonuses_amount');
        $other = self::parseNonNegativeMoney($otherAllowances, 'other_allowances_amount');

        return round($basic + $allow + $bonus + $other, 2);
    }

    /**
     * Validate and normalize salary settings payload. Rejects compensation minutes in this phase.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function validateAndNormalizePayload(array $input, ?Employee $employee = null): array
    {
        if (array_key_exists('compensation_minutes', $input) && $input['compensation_minutes'] !== null && $input['compensation_minutes'] !== '') {
            throw ValidationException::withMessages([
                'compensation_minutes' => __('employees.salary_profile.errors.compensation_minutes_unsupported'),
            ]);
        }

        if ($employee !== null && ! self::isEligibleSaudiEmployee($employee)) {
            throw ValidationException::withMessages([
                'employee_id' => __('employees.salary_profile.errors.ineligible_employee'),
            ]);
        }

        $basic = self::parseNonNegativeMoney($input['basic_salary'] ?? ($employee?->basic_salary ?? 0), 'basic_salary');
        $allowances = self::parseNonNegativeMoney($input['allowances_amount'] ?? ($employee?->allowances_amount ?? 0), 'allowances_amount');
        $bonuses = self::parseNonNegativeMoney($input['bonuses_amount'] ?? ($employee?->bonuses_amount ?? 0), 'bonuses_amount');
        $otherAllowances = self::parseNonNegativeMoney($input['other_allowances_amount'] ?? ($employee?->other_allowances_amount ?? 0), 'other_allowances_amount');

        $overtimeRate = array_key_exists('overtime_hourly_rate', $input)
            ? self::parseNullableNonNegativeMoney($input['overtime_hourly_rate'], 'overtime_hourly_rate')
            : ($employee?->overtime_hourly_rate !== null ? (float) $employee->overtime_hourly_rate : null);

        $compensationRate = array_key_exists('compensation_hourly_rate', $input)
            ? self::parseNullableNonNegativeMoney($input['compensation_hourly_rate'], 'compensation_hourly_rate')
            : ($employee?->compensation_hourly_rate !== null ? (float) $employee->compensation_hourly_rate : null);

        $basisRaw = $input['deduction_basis']
            ?? ($employee?->deduction_basis instanceof DeductionBasis ? $employee->deduction_basis->value : ($employee?->deduction_basis ?? DeductionBasis::BASIC->value));
        if ($basisRaw instanceof DeductionBasis) {
            $basisRaw = $basisRaw->value;
        }
        if (! in_array((string) $basisRaw, DeductionBasis::values(), true)) {
            throw ValidationException::withMessages([
                'deduction_basis' => __('employees.salary_profile.errors.invalid_deduction_basis'),
            ]);
        }

        $hasDailyHours = array_key_exists('daily_work_hours', $input);
        $dailyHours = $hasDailyHours
            ? self::parseNullableWorkHours($input['daily_work_hours'], 'daily_work_hours', 24.0)
            : ($employee?->daily_work_hours !== null ? (float) $employee->daily_work_hours : null);

        $hasWeeklyHours = array_key_exists('weekly_work_hours', $input);
        $weeklyHours = $hasWeeklyHours
            ? self::parseNullableWorkHours($input['weekly_work_hours'], 'weekly_work_hours', 168.0)
            : ($employee?->weekly_work_hours !== null ? (float) $employee->weekly_work_hours : null);

        return [
            'basic_salary' => $basic,
            'allowances_amount' => $allowances,
            'bonuses_amount' => $bonuses,
            'other_allowances_amount' => $otherAllowances,
            'total_salary' => round($basic + $allowances + $bonuses + $otherAllowances, 2),
            'overtime_hourly_rate' => $overtimeRate,
            'compensation_hourly_rate' => $compensationRate,
            'deduction_basis' => (string) $basisRaw,
            'has_daily_work_hours' => $hasDailyHours,
            'daily_work_hours' => $dailyHours,
            'has_weekly_work_hours' => $hasWeeklyHours,
            'weekly_work_hours' => $weeklyHours,
        ];
    }

    /**
     * Persist salary settings on Employee and record diff in UnifiedAuditLog within a single locked transaction.
     *
     * @param  array<string, mixed>  $input
     */
    public static function saveForSaudiEmployee(Employee $employee, array $input, ?User $actor = null): Employee
    {
        $normalized = self::validateAndNormalizePayload($input, $employee);

        return DB::transaction(function () use ($employee, $normalized, $actor): Employee {
            $lockedEmployee = Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            if (! self::isEligibleSaudiEmployee($lockedEmployee)) {
                throw ValidationException::withMessages([
                    'employee_id' => __('employees.salary_profile.errors.ineligible_employee'),
                ]);
            }

            if (empty($lockedEmployee->company_id)) {
                throw ValidationException::withMessages([
                    'company_id' => __('employees.salary_profile.errors.missing_company'),
                ]);
            }

            $before = self::extractAuditState($lockedEmployee);

            $lockedEmployee->basic_salary = $normalized['basic_salary'];
            $lockedEmployee->allowances_amount = $normalized['allowances_amount'];
            $lockedEmployee->bonuses_amount = $normalized['bonuses_amount'];
            $lockedEmployee->other_allowances_amount = $normalized['other_allowances_amount'];
            $lockedEmployee->overtime_hourly_rate = $normalized['overtime_hourly_rate'];
            $lockedEmployee->compensation_hourly_rate = $normalized['compensation_hourly_rate'];
            $lockedEmployee->deduction_basis = $normalized['deduction_basis'];
            $lockedEmployee->is_policy_complete = false;

            if ($normalized['has_daily_work_hours']) {
                $lockedEmployee->daily_work_hours = $normalized['daily_work_hours'];
            }
            if ($normalized['has_weekly_work_hours']) {
                $lockedEmployee->weekly_work_hours = $normalized['weekly_work_hours'];
            }

            $lockedEmployee->save();

            $after = self::extractAuditState($lockedEmployee);

            if ($before !== $after) {
                OperationsAuditService::recordFor(
                    model: $lockedEmployee,
                    actionType: self::AUDIT_ACTION,
                    oldStatus: $before['deduction_basis'],
                    newStatus: $after['deduction_basis'],
                    payload: [
                        'before' => $before,
                        'after' => $after,
                        'effective_date' => now()->toDateString(),
                    ],
                    userId: $actor?->id ?? auth()->id(),
                    companyId: (int) $lockedEmployee->company_id,
                );
            }

            return $lockedEmployee->refresh();
        });
    }

    /**
     * Resolve form-ready salary settings data from Employee.
     *
     * @return array<string, mixed>
     */
    public static function getFormData(Employee $employee): array
    {
        $basic = (float) ($employee->basic_salary ?? 0);
        $allowances = (float) ($employee->allowances_amount ?? 0);
        $bonuses = (float) ($employee->bonuses_amount ?? 0);
        $other = (float) ($employee->other_allowances_amount ?? 0);

        $basis = $employee->deduction_basis instanceof DeductionBasis
            ? $employee->deduction_basis->value
            : ($employee->deduction_basis ?: DeductionBasis::BASIC->value);

        return [
            'basic_salary' => $basic,
            'allowances_amount' => $allowances,
            'bonuses_amount' => $bonuses,
            'other_allowances_amount' => $other,
            'total_salary_display' => round($basic + $allowances + $bonuses + $other, 2),
            'overtime_hourly_rate' => $employee->overtime_hourly_rate !== null ? (float) $employee->overtime_hourly_rate : null,
            'compensation_hourly_rate' => $employee->compensation_hourly_rate !== null ? (float) $employee->compensation_hourly_rate : null,
            'deduction_basis' => $basis,
            'daily_work_hours' => $employee->daily_work_hours !== null ? (float) $employee->daily_work_hours : null,
            'weekly_work_hours' => $employee->weekly_work_hours !== null ? (float) $employee->weekly_work_hours : null,
        ];
    }

    /**
     * Build a read-only calculation snapshot for payroll_records.metadata.calculation_snapshot.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function buildCalculationSnapshot(Employee $employee, array $overrides = []): array
    {
        $basic = (float) ($overrides['basic_salary'] ?? $employee->basic_salary ?? 0);
        $allowances = (float) ($employee->allowances_amount ?? 0);
        $bonuses = (float) ($employee->bonuses_amount ?? 0);
        $other = (float) ($employee->other_allowances_amount ?? 0);
        $basis = $employee->deduction_basis instanceof DeductionBasis
            ? $employee->deduction_basis->value
            : ($employee->deduction_basis ?: DeductionBasis::BASIC->value);

        return [
            'version' => self::SNAPSHOT_VERSION,
            'captured_at' => now()->toIso8601String(),
            'is_policy_complete' => false,
            'basic_salary' => $basic,
            'allowances_amount' => $allowances,
            'bonuses_amount' => $bonuses,
            'other_allowances_amount' => $other,
            'total_salary' => round($basic + $allowances + $bonuses + $other, 2),
            'overtime_hourly_rate' => $employee->overtime_hourly_rate !== null ? (float) $employee->overtime_hourly_rate : null,
            'compensation_hourly_rate' => $employee->compensation_hourly_rate !== null ? (float) $employee->compensation_hourly_rate : null,
            'deduction_basis' => $basis,
            'daily_work_hours' => $employee->daily_work_hours !== null ? (float) $employee->daily_work_hours : null,
            'weekly_work_hours' => $employee->weekly_work_hours !== null ? (float) $employee->weekly_work_hours : null,
        ];
    }

    /**
     * Resolve reference salary data for a given period: payroll calculation_snapshot if available, else current Employee reference.
     *
     * @return array<string, mixed>
     */
    public static function resolveReferenceForPeriod(Employee $employee, string $startDate, string $endDate): array
    {
        $payrollRecord = PayrollRecord::query()
            ->where('employee_id', $employee->id)
            ->where('operation_type', 'payroll')
            ->whereDate('payroll_month', '>=', substr($startDate, 0, 7).'-01')
            ->whereDate('payroll_month', '<=', substr($endDate, 0, 7).'-01')
            ->orderByDesc('payroll_month')
            ->orderByDesc('id')
            ->first();

        $snapshot = is_array($payrollRecord?->metadata['calculation_snapshot'] ?? null)
            ? $payrollRecord->metadata['calculation_snapshot']
            : null;

        if ($snapshot !== null) {
            return self::formatReferenceArray($snapshot, true, $payrollRecord?->payroll_month?->toDateString());
        }

        return self::formatReferenceArray(self::buildCalculationSnapshot($employee), false, null);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function formatReferenceArray(array $data, bool $isDatedSnapshot, ?string $snapshotMonth = null): array
    {
        $basic = (float) ($data['basic_salary'] ?? 0);
        $allowances = (float) ($data['allowances_amount'] ?? 0);
        $bonuses = (float) ($data['bonuses_amount'] ?? 0);
        $other = (float) ($data['other_allowances_amount'] ?? 0);
        $basis = DeductionBasis::tryFrom((string) ($data['deduction_basis'] ?? 'basic')) ?? DeductionBasis::BASIC;

        return [
            'is_dated_snapshot' => $isDatedSnapshot,
            'snapshot_month' => $snapshotMonth,
            'basic_salary' => $basic,
            'allowances_amount' => $allowances,
            'bonuses_amount' => $bonuses,
            'other_allowances_amount' => $other,
            'total_salary' => round($basic + $allowances + $bonuses + $other, 2),
            'overtime_hourly_rate' => isset($data['overtime_hourly_rate']) && $data['overtime_hourly_rate'] !== null ? (float) $data['overtime_hourly_rate'] : null,
            'compensation_hourly_rate' => isset($data['compensation_hourly_rate']) && $data['compensation_hourly_rate'] !== null ? (float) $data['compensation_hourly_rate'] : null,
            'deduction_basis' => $basis->value,
            'deduction_basis_label' => $basis->getLabel(),
            'daily_work_hours' => isset($data['daily_work_hours']) && $data['daily_work_hours'] !== null ? (float) $data['daily_work_hours'] : null,
            'weekly_work_hours' => isset($data['weekly_work_hours']) && $data['weekly_work_hours'] !== null ? (float) $data['weekly_work_hours'] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function extractAuditState(Employee $employee): array
    {
        $basis = $employee->deduction_basis instanceof DeductionBasis
            ? $employee->deduction_basis->value
            : (string) ($employee->deduction_basis ?? DeductionBasis::BASIC->value);

        return [
            'basic_salary' => round((float) ($employee->basic_salary ?? 0), 2),
            'allowances_amount' => round((float) ($employee->allowances_amount ?? 0), 2),
            'bonuses_amount' => round((float) ($employee->bonuses_amount ?? 0), 2),
            'other_allowances_amount' => round((float) ($employee->other_allowances_amount ?? 0), 2),
            'total_salary' => self::calculateTotalSalary(
                $employee->basic_salary,
                $employee->allowances_amount,
                $employee->bonuses_amount,
                $employee->other_allowances_amount
            ),
            'overtime_hourly_rate' => $employee->overtime_hourly_rate !== null ? round((float) $employee->overtime_hourly_rate, 2) : null,
            'compensation_hourly_rate' => $employee->compensation_hourly_rate !== null ? round((float) $employee->compensation_hourly_rate, 2) : null,
            'deduction_basis' => $basis,
            'daily_work_hours' => $employee->daily_work_hours !== null ? round((float) $employee->daily_work_hours, 1) : null,
            'weekly_work_hours' => $employee->weekly_work_hours !== null ? round((float) $employee->weekly_work_hours, 1) : null,
        ];
    }

    private static function parseNonNegativeMoney(mixed $value, string $field): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (! is_numeric($value)) {
            throw ValidationException::withMessages([
                $field => __('employees.salary_profile.errors.invalid_numeric'),
            ]);
        }

        $parsed = round((float) $value, 2);
        if ($parsed < 0) {
            throw ValidationException::withMessages([
                $field => __('employees.salary_profile.errors.negative_amount'),
            ]);
        }

        return $parsed;
    }

    private static function parseNullableNonNegativeMoney(mixed $value, string $field): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw ValidationException::withMessages([
                $field => __('employees.salary_profile.errors.invalid_numeric'),
            ]);
        }

        $parsed = round((float) $value, 2);
        if ($parsed < 0) {
            throw ValidationException::withMessages([
                $field => __('employees.salary_profile.errors.negative_amount'),
            ]);
        }

        return $parsed;
    }

    private static function parseNullableWorkHours(mixed $value, string $field, float $max): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw ValidationException::withMessages([
                $field => __('employees.salary_profile.errors.invalid_numeric'),
            ]);
        }

        $parsed = round((float) $value, 1);
        if ($parsed < 0 || $parsed > $max) {
            throw ValidationException::withMessages([
                $field => __('employees.salary_profile.errors.invalid_work_hours'),
            ]);
        }

        return $parsed;
    }
}
