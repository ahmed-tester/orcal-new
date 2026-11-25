<?php

namespace App\Services\Attendance;

use App\Services\Payroll\EmployeeSalaryProfileService;
use App\Services\Payroll\SaudiCompensationGrantService;
use App\Services\Payroll\SaudiPayrollPolicyService;
use App\Enums\AttendanceSource;
use App\Enums\AttendanceStatus;
use App\Enums\EmploymentType;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\PayrollRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SaudiAttendancePeriodReportService
{
    public const PROVISIONAL_OVERTIME_NOTE_KEY = 'attendance.period_report.overtime_source_note';

    public static function canViewFinancialDetails(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->isSuperAdmin() || $user->can('view_any_payroll_record');
    }

    /**
     * Resolve [startDate, endDate] for day/week/month/custom without forcing month override.
     *
     * @param  array<string, mixed>  $filters
     * @return array{0: string, 1: string}
     */
    public static function resolvePeriodBounds(array $filters): array
    {
        $preset = (string) ($filters['period_preset'] ?? 'month');
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;

        if (! empty($dateFrom) && ! empty($dateTo)) {
            $start = Carbon::parse($dateFrom)->startOfDay();
            $end = Carbon::parse($dateTo)->startOfDay();
            if ($end->lt($start)) {
                [$start, $end] = [$end, $start];
            }

            return [$start->toDateString(), $end->toDateString()];
        }

        $anchor = ! empty($dateFrom)
            ? Carbon::parse($dateFrom)->startOfDay()
            : now()->startOfDay();

        return match ($preset) {
            'day' => [$anchor->toDateString(), $anchor->toDateString()],
            'week' => [$anchor->copy()->subDays(6)->toDateString(), $anchor->toDateString()],
            'month' => [
                $anchor->copy()->startOfMonth()->toDateString(),
                $anchor->copy()->endOfMonth()->toDateString(),
            ],
            default => AttendanceSummaryService::resolveDateRange([
                'date_from' => $dateFrom ?: $anchor->copy()->startOfMonth()->toDateString(),
                'date_to' => $dateTo ?: $anchor->copy()->endOfMonth()->toDateString(),
            ]),
        };
    }

    /**
     * Build summary rows and totals for Saudi non-temporary employees over the period.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public static function buildReport(array $filters, ?User $user = null): array
    {
        [$startDate, $endDate] = self::resolvePeriodBounds($filters);
        $canViewFinancial = self::canViewFinancialDetails($user);

        $employees = self::scopedSaudiEmployeesQuery($filters)
            ->with(['user', 'company', 'branch', 'details'])
            ->orderBy('employees.id')
            ->get();

        $employeeIds = $employees->pluck('id')->all();

        $attendanceByEmployee = empty($employeeIds)
            ? collect()
            : Attendance::query()
                ->whereIn('employee_id', $employeeIds)
                ->whereDate('date', '>=', $startDate)
                ->whereDate('date', '<=', $endDate)
                ->orderBy('date')
                ->orderBy('id')
                ->get()
                ->groupBy('employee_id');

        $snapshotsByEmployee = ($canViewFinancial && ! empty($employeeIds))
            ? self::loadSnapshotsForEmployees($employeeIds, $startDate, $endDate)
            : collect();

        $rows = collect();
        $totals = [
            'employees_count' => 0,
            'recorded_days' => 0,
            'present_days' => 0,
            'late_days' => 0,
            'absent_days' => 0,
            'leave_days' => 0,
            'late_minutes' => 0,
            'overtime_minutes' => 0,
            'total_hours' => 0.0,
            'provisional_overtime_cost_total' => null,
        ];

        $overtimeCostAccumulator = 0.0;
        $hasAnyOvertimeEstimate = false;
        $hasActivePolicy = false;
        $totals['late_deduction_amount'] = 0.0;
        $totals['compensation_minutes_used'] = 0.0;
        $totals['compensation_amount'] = 0.0;
        $totals['remaining_late_deduction'] = 0.0;

        foreach ($employees as $employee) {
            /** @var Collection<int, Attendance> $records */
            $records = $attendanceByEmployee->get($employee->id, collect());

            $recordedDays = $records->count();
            $presentDays = $records->where('status', AttendanceStatus::PRESENT)->count();
            $lateDays = $records->where('status', AttendanceStatus::LATE)->count();
            $absentDays = $records->where('status', AttendanceStatus::ABSENT)->count();
            $leaveDays = $records->filter(fn (Attendance $att) => in_array($att->status, [AttendanceStatus::EXCUSED, AttendanceStatus::LEAVE], true))->count();
            $lateMinutes = (int) $records->sum(fn (Attendance $att) => (int) ($att->late_hours ?? 0));
            $overtimeMinutes = (int) $records->sum(fn (Attendance $att) => (int) ($att->overtime_hours ?? 0));
            $totalHours = round((float) $records->sum(fn (Attendance $att) => (float) ($att->total_hours ?? 0)), 2);

            $salaryReference = null;
            $overtimeCostEstimate = null;
            $policySummary = null;

            if ($canViewFinancial) {
                $employeeSnapshots = $snapshotsByEmployee->get($employee->id, collect());
                $salaryReference = self::resolveReferenceFromPreloaded(
                    $employee,
                    $employeeSnapshots->first()
                );

                $overtimeCostEstimate = self::computeCrossMonthOvertimeCost(
                    $employee,
                    $records,
                    $employeeSnapshots
                );

                if ($overtimeCostEstimate !== null) {
                    $overtimeCostAccumulator += $overtimeCostEstimate;
                    $hasAnyOvertimeEstimate = true;
                }

                $policySummary = self::computePeriodPolicySummary($employee, $records, $startDate, $endDate, $employeeSnapshots);
                if (! empty($policySummary['active'])) {
                    $hasActivePolicy = true;
                    $totals['late_deduction_amount'] = round($totals['late_deduction_amount'] + $policySummary['late_deduction_amount'], 2);
                    $totals['compensation_minutes_used'] = round($totals['compensation_minutes_used'] + $policySummary['compensation_minutes_used'], 2);
                    $totals['compensation_amount'] = round($totals['compensation_amount'] + $policySummary['compensation_amount'], 2);
                    $totals['remaining_late_deduction'] = round($totals['remaining_late_deduction'] + $policySummary['remaining_late_deduction'], 2);
                }
            }

            $totals['employees_count']++;
            $totals['recorded_days'] += $recordedDays;
            $totals['present_days'] += $presentDays;
            $totals['late_days'] += $lateDays;
            $totals['absent_days'] += $absentDays;
            $totals['leave_days'] += $leaveDays;
            $totals['late_minutes'] += $lateMinutes;
            $totals['overtime_minutes'] += $overtimeMinutes;
            $totals['total_hours'] = round($totals['total_hours'] + $totalHours, 2);

            $rows->push([
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'employee_name' => $employee->user?->name ?? '—',
                'national_id' => $employee->details?->national_id ?? '—',
                'company_name' => $employee->company?->trade_name ?? '—',
                'branch_name' => $employee->branch?->name ?? '—',
                'recorded_days' => $recordedDays,
                'present_days' => $presentDays,
                'late_days' => $lateDays,
                'absent_days' => $absentDays,
                'leave_days' => $leaveDays,
                'late_minutes' => $lateMinutes,
                'overtime_minutes' => $overtimeMinutes,
                'total_hours' => $totalHours,
                'salary_reference' => $salaryReference,
                'overtime_cost_estimate' => $overtimeCostEstimate,
                'overtime_cost_is_provisional' => true,
                'policy_summary' => $policySummary,
            ]);
        }

        if ($canViewFinancial && $hasAnyOvertimeEstimate) {
            $totals['provisional_overtime_cost_total'] = round($overtimeCostAccumulator, 2);
        }

        return [
            'date_from' => $startDate,
            'date_to' => $endDate,
            'can_view_financial' => $canViewFinancial,
            'has_active_policy' => $hasActivePolicy,
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    /**
     * Build daily attendance records and optional financial reference for a single employee period modal/sheet.
     *
     * @return array<string, mixed>
     */
    public static function buildEmployeePeriodDetails(
        Employee $employee,
        string $startDate,
        string $endDate,
        ?User $user = null,
    ): array {
        $canViewFinancial = self::canViewFinancialDetails($user);

        $rawRecords = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $records = $rawRecords
            ->map(function (Attendance $att): array {
                $statusEnum = $att->status instanceof AttendanceStatus ? $att->status : AttendanceStatus::tryFrom((string) $att->status);

                return [
                    'date' => $att->date?->format('Y-m-d') ?? '—',
                    'status_label' => $statusEnum?->getLabel() ?? (string) $att->status,
                    'check_in' => $att->check_in ? Carbon::parse($att->check_in)->format('H:i') : '—',
                    'check_out' => $att->check_out ? Carbon::parse($att->check_out)->format('H:i') : '—',
                    'late_minutes' => (int) ($att->late_hours ?? 0),
                    'overtime_minutes' => (int) ($att->overtime_hours ?? 0),
                    'total_hours' => round((float) ($att->total_hours ?? 0), 2),
                    'source_label' => self::formatAttendanceSourceLabel($att),
                ];
            })
            ->values();

        $salaryReference = $canViewFinancial
            ? EmployeeSalaryProfileService::resolveReferenceForPeriod($employee, $startDate, $endDate)
            : null;

        $overtimeMinutes = (int) $records->sum('overtime_minutes');
        $overtimeCostEstimate = null;
        $policySummary = null;
        if ($canViewFinancial) {
            $employeeSnapshots = self::loadSnapshotsForEmployees([$employee->id], $startDate, $endDate)
                ->get($employee->id, collect());
            $overtimeCostEstimate = self::computeCrossMonthOvertimeCost($employee, $rawRecords, $employeeSnapshots);
            $policySummary = self::computePeriodPolicySummary($employee, $rawRecords, $startDate, $endDate, $employeeSnapshots);
        }

        return [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->user?->name ?? '—',
                'employee_number' => $employee->employee_number ?? '—',
                'company_name' => $employee->company?->trade_name ?? '—',
                'branch_name' => $employee->branch?->name ?? '—',
            ],
            'date_from' => $startDate,
            'date_to' => $endDate,
            'can_view_financial' => $canViewFinancial,
            'records' => $records,
            'totals' => [
                'recorded_days' => $records->count(),
                'late_minutes' => (int) $records->sum('late_minutes'),
                'overtime_minutes' => $overtimeMinutes,
                'total_hours' => round((float) $records->sum('total_hours'), 2),
                'overtime_cost_estimate' => $overtimeCostEstimate,
                'policy_summary' => $policySummary,
            ],
            'salary_reference' => $salaryReference,
        ];
    }

    /**
     * Build UTF-8 BOM CSV content from the exact same report data.
     *
     * @param  array<string, mixed>  $filters
     */
    public static function buildCsv(array $filters, ?User $user = null): string
    {
        $report = self::buildReport($filters, $user);
        $canViewFinancial = $report['can_view_financial'];

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");

        $headers = [
            __('attendance.period_report.columns.employee_number'),
            __('attendance.period_report.columns.employee_name'),
            __('attendance.period_report.columns.company'),
            __('attendance.period_report.columns.branch'),
            __('attendance.period_report.columns.recorded_days'),
            __('attendance.period_report.columns.present_days'),
            __('attendance.period_report.columns.late_days'),
            __('attendance.period_report.columns.absent_days'),
            __('attendance.period_report.columns.leave_days'),
            __('attendance.period_report.columns.late_minutes'),
            __('attendance.period_report.columns.overtime_minutes'),
            __('attendance.period_report.columns.total_hours'),
        ];

        if ($canViewFinancial) {
            $headers[] = __('attendance.period_report.columns.reference_total_salary');
            $headers[] = __('attendance.period_report.columns.overtime_hourly_rate');
            $headers[] = __('attendance.period_report.columns.overtime_cost_estimate_export');
            if ($report['has_active_policy']) {
                $headers[] = __('attendance.period_report.columns.late_deduction');
                $headers[] = __('attendance.period_report.columns.compensation_minutes_used');
                $headers[] = __('attendance.period_report.columns.compensation_amount');
                $headers[] = __('attendance.period_report.columns.remaining_late_deduction');
            }
        }

        fputcsv($handle, $headers);

        foreach ($report['rows'] as $row) {
            $csvRow = [
                $row['employee_number'],
                $row['employee_name'],
                $row['company_name'],
                $row['branch_name'],
                $row['recorded_days'],
                $row['present_days'],
                $row['late_days'],
                $row['absent_days'],
                $row['leave_days'],
                $row['late_minutes'],
                $row['overtime_minutes'],
                $row['total_hours'],
            ];

            if ($canViewFinancial) {
                $ref = $row['salary_reference'];
                $csvRow[] = $ref ? number_format((float) $ref['total_salary'], 2, '.', '') : '';
                $csvRow[] = ($ref && $ref['overtime_hourly_rate'] !== null)
                    ? number_format((float) $ref['overtime_hourly_rate'], 2, '.', '')
                    : __('employees.salary_profile.placeholders.unspecified');
                $csvRow[] = $row['overtime_cost_estimate'] !== null
                    ? number_format((float) $row['overtime_cost_estimate'], 2, '.', '').' ('.__('attendance.period_report.provisional_estimate_badge').')'
                    : __('attendance.period_report.pending_calculation');

                if ($report['has_active_policy']) {
                    $pol = $row['policy_summary'];
                    $csvRow[] = ($pol && $pol['active']) ? number_format((float) $pol['late_deduction_amount'], 2, '.', '') : '—';
                    $csvRow[] = ($pol && $pol['active']) ? number_format((float) $pol['compensation_minutes_used'], 2, '.', '') : '—';
                    $csvRow[] = ($pol && $pol['active']) ? number_format((float) $pol['compensation_amount'], 2, '.', '') : '—';
                    $csvRow[] = ($pol && $pol['active']) ? number_format((float) $pol['remaining_late_deduction'], 2, '.', '') : '—';
                }
            }

            fputcsv($handle, $csvRow);
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private static function scopedSaudiEmployeesQuery(array $filters): Builder
    {
        $query = AttendanceSummaryService::applyAccessScope(Employee::query())
            ->saudi()
            ->where(function (Builder $q): void {
                $q->whereNull('employees.employment_type')
                    ->orWhere('employees.employment_type', '!=', EmploymentType::TEMPORARY->value);
            });

        if (! empty($filters['company_id'])) {
            $companyIds = array_filter(array_map('intval', (array) $filters['company_id']));
            if (! empty($companyIds)) {
                $query->whereIn('employees.company_id', $companyIds);
            }
        }

        if (! empty($filters['branch_id'])) {
            $branchIds = array_filter(array_map('intval', (array) $filters['branch_id']));
            if (! empty($branchIds)) {
                $query->whereIn('employees.branch_id', $branchIds);
            }
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $q) use ($search): void {
                $q->where('employees.employee_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $uq) => $uq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('details', fn (Builder $dq) => $dq->where('national_id', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    /**
     * @param  list<int>  $employeeIds
     * @return Collection<int, Collection<string, PayrollRecord>>
     */
    private static function loadSnapshotsForEmployees(array $employeeIds, string $startDate, string $endDate): Collection
    {
        $startMonth = substr($startDate, 0, 7).'-01';
        $endMonth = substr($endDate, 0, 7).'-01';

        return PayrollRecord::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('operation_type', 'payroll')
            ->whereDate('payroll_month', '>=', $startMonth)
            ->whereDate('payroll_month', '<=', $endMonth)
            ->orderByDesc('payroll_month')
            ->orderByDesc('id')
            ->get()
            ->groupBy('employee_id')
            ->map(function (Collection $records): Collection {
                return $records->keyBy(fn (PayrollRecord $r) => $r->payroll_month?->format('Y-m') ?? '');
            });
    }

    /**
     * Split overtime cost calculation by calendar month so cross-month periods
     * use each month's own snapshot rate rather than applying the latest month's rate to all months.
     *
     * @param  Collection<int, Attendance>  $records
     * @param  Collection<string, PayrollRecord>  $employeeSnapshots
     */
    private static function computeCrossMonthOvertimeCost(
        Employee $employee,
        Collection $records,
        Collection $employeeSnapshots,
    ): ?float {
        $byMonth = $records->groupBy(fn (Attendance $att) => $att->date?->format('Y-m') ?? '');
        if ($byMonth->isEmpty()) {
            $rate = $employee->overtime_hourly_rate;

            return $rate !== null ? 0.0 : null;
        }

        $totalCost = 0.0;
        $hasAnyOvertime = false;

        foreach ($byMonth as $monthKey => $monthRecords) {
            $monthMinutes = (int) $monthRecords->sum(fn (Attendance $att) => (int) ($att->overtime_hours ?? 0));
            if ($monthMinutes <= 0) {
                continue;
            }

            $hasAnyOvertime = true;
            /** @var PayrollRecord|null $snapshotRecord */
            $snapshotRecord = $employeeSnapshots->get($monthKey);
            $snapshot = is_array($snapshotRecord?->metadata['calculation_snapshot'] ?? null)
                ? $snapshotRecord->metadata['calculation_snapshot']
                : null;

            $rate = $snapshot !== null
                ? ($snapshot['overtime_hourly_rate'] ?? null)
                : $employee->overtime_hourly_rate;

            if ($rate === null) {
                return null;
            }

            $totalCost += round(($monthMinutes / 60) * (float) $rate, 2);
        }

        if (! $hasAnyOvertime) {
            $fallbackRate = $employeeSnapshots->first()?->metadata['calculation_snapshot']['overtime_hourly_rate']
                ?? $employee->overtime_hourly_rate;

            return $fallbackRate !== null ? 0.0 : null;
        }

        return round($totalCost, 2);
    }

    private static function formatAttendanceSourceLabel(Attendance $att): string
    {
        $resolve = static function (mixed $raw): ?string {
            if ($raw instanceof AttendanceSource) {
                return $raw->getLabel();
            }
            if (is_string($raw) && $raw !== '') {
                return AttendanceSource::tryFrom($raw)?->getLabel() ?? $raw;
            }

            return null;
        };

        $inLabel = $resolve($att->check_in_source);
        $outLabel = $resolve($att->check_out_source);

        if ($inLabel !== null && $outLabel !== null) {
            return $inLabel === $outLabel ? $inLabel : "{$inLabel} / {$outLabel}";
        }

        if ($inLabel !== null || $outLabel !== null) {
            return $inLabel ?? $outLabel;
        }

        return $resolve($att->getAttribute('source')) ?? '—';
    }

    /**
     * Read-only period summary for late deduction and compensation under the active company policy.
     * Uses each month's own calculation_days / snapshot / grants without mutating or reserving balances.
     *
     * @param  Collection<int, Attendance>  $records
     * @param  Collection<string, PayrollRecord>  $employeeSnapshots
     * @return array<string, mixed>
     */
    private static function computePeriodPolicySummary(
        Employee $employee,
        Collection $records,
        string $startDate,
        string $endDate,
        Collection $employeeSnapshots,
    ): array {
        $startMonth = Carbon::parse($startDate)->startOfMonth();
        $endMonth = Carbon::parse($endDate)->startOfMonth();

        $months = [];
        $cursor = $startMonth->copy();
        while ($cursor->lte($endMonth)) {
            $months[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        $active = false;
        $lateDeductionTotal = 0.0;
        $compMinutesTotal = 0.0;
        $compAmountTotal = 0.0;
        $sharedOnceCycleUsage = [];

        foreach ($months as $monthYm) {
            /** @var PayrollRecord|null $snapshotRecord */
            $snapshotRecord = $employeeSnapshots->get($monthYm);
            $resolved = SaudiPayrollPolicyService::resolveForPayrollMonth(
                $employee,
                $monthYm.'-01',
                $snapshotRecord,
                false
            );

            if (empty($resolved['use_new_policy'])) {
                continue;
            }

            $active = true;
            $monthRecords = $records->filter(fn (Attendance $att) => $att->date?->format('Y-m') === $monthYm);
            $monthLateMinutes = (int) $monthRecords->sum(fn (Attendance $att) => (int) ($att->late_hours ?? 0));
            if ($monthLateMinutes <= 0) {
                continue;
            }

            $monthStart = max($startDate, $monthYm.'-01');
            $monthEnd = min($endDate, Carbon::parse($monthYm.'-01')->endOfMonth()->toDateString());

            $savedAllocations = ($snapshotRecord !== null
                && $snapshotRecord->hasFinancialImpact()
                && is_array($snapshotRecord->metadata['compensation_allocations'] ?? null))
                ? $snapshotRecord->metadata['compensation_allocations']
                : null;

            if ($savedAllocations !== null) {
                $hourRate = (float) ($resolved['hour_rate'] ?? 0);
                $monthLateDeduction = round(($monthLateMinutes / 60) * $hourRate, 2);
                $lateDeductionTotal += $monthLateDeduction;

                $monthCompMinutes = 0.0;
                $monthCompAmount = 0.0;
                foreach ($savedAllocations as $alloc) {
                    if (! in_array(($alloc['status'] ?? ''), ['reserved', 'confirmed'], true)) {
                        continue;
                    }
                    $allocDate = (string) ($alloc['date'] ?? '');
                    if ($allocDate === '' || $allocDate < $monthStart || $allocDate > $monthEnd) {
                        continue;
                    }
                    $monthCompMinutes += (float) ($alloc['consumed_minutes'] ?? 0);
                    $monthCompAmount += (float) ($alloc['amount'] ?? 0);
                }

                $compMinutesTotal += round($monthCompMinutes, 2);
                $compAmountTotal += min($monthLateDeduction, round($monthCompAmount, 2));

                continue;
            }

            $calc = SaudiCompensationGrantService::calculateMonthlyLateAndCompensation(
                $employee,
                $monthYm.'-01',
                $resolved,
                $snapshotRecord?->id,
                null,
                $monthStart,
                $monthEnd,
                $sharedOnceCycleUsage
            );

            $lateDeductionTotal += (float) $calc['late_deduction_amount'];

            if (empty($calc['blocked_missing_comp_rate'])) {
                $compMinutesTotal += (float) $calc['compensation_minutes_used'];
                $compAmountTotal += (float) $calc['compensation_amount'];
            }
        }

        $lateDeductionTotal = round($lateDeductionTotal, 2);
        $compAmountTotal = min($lateDeductionTotal, round($compAmountTotal, 2));

        return [
            'active' => $active,
            'late_deduction_amount' => $lateDeductionTotal,
            'compensation_minutes_used' => round($compMinutesTotal, 2),
            'compensation_amount' => $compAmountTotal,
            'remaining_late_deduction' => round(max(0.0, $lateDeductionTotal - $compAmountTotal), 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function resolveReferenceFromPreloaded(Employee $employee, ?PayrollRecord $payrollRecord): array
    {
        $snapshot = is_array($payrollRecord?->metadata['calculation_snapshot'] ?? null)
            ? $payrollRecord->metadata['calculation_snapshot']
            : null;

        if ($snapshot !== null) {
            return EmployeeSalaryProfileService::formatReferenceArray(
                $snapshot,
                true,
                $payrollRecord?->payroll_month?->toDateString()
            );
        }

        return EmployeeSalaryProfileService::formatReferenceArray(
            EmployeeSalaryProfileService::buildCalculationSnapshot($employee),
            false,
            null
        );
    }
}
