<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\GeneralStatus;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AttendanceSummaryService
{
    protected static array $arabicDayNames = [
        0 => 'الأحد',
        1 => 'الاثنين',
        2 => 'الثلاثاء',
        3 => 'الأربعاء',
        4 => 'الخميس',
        5 => 'الجمعة',
        6 => 'السبت',
    ];

    protected static array $arabicMonthNames = [
        1 => 'يناير',
        2 => 'فبراير',
        3 => 'مارس',
        4 => 'أبريل',
        5 => 'مايو',
        6 => 'يونيو',
        7 => 'يوليو',
        8 => 'أغسطس',
        9 => 'سبتمبر',
        10 => 'أكتوبر',
        11 => 'نوفمبر',
        12 => 'ديسمبر',
    ];

    /**
     * Build the aggregated summary query where each row is an Employee
     * with metrics computed over the selected month/period.
     */
    public static function summaryQuery(array $filters = []): Builder
    {
        [$startDate, $endDate] = self::resolveDateRange($filters);

        $aggregate = Attendance::query()
            ->selectRaw('
                employee_id,
                COUNT(*) as recorded_days,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as leave_days,
                SUM(COALESCE(late_hours, 0)) as late_minutes,
                SUM(COALESCE(overtime_hours, 0)) as overtime_minutes,
                SUM(COALESCE(total_hours, 0)) as total_hours,
                MAX(date) as last_date
            ', [
                AttendanceStatus::PRESENT->value,
                AttendanceStatus::LATE->value,
                AttendanceStatus::ABSENT->value,
                AttendanceStatus::EXCUSED->value,
                AttendanceStatus::LEAVE->value,
            ])
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('employee_id');

        $query = Employee::query()
            ->saudi()
            ->leftJoinSub($aggregate, 'att', fn ($join) => $join->on('att.employee_id', '=', 'employees.id'))
            ->select([
                'employees.*',
            ])
            ->selectRaw('
                COALESCE(att.recorded_days, 0) as recorded_days,
                COALESCE(att.present_days, 0) as present_days,
                COALESCE(att.late_days, 0) as late_days,
                COALESCE(att.absent_days, 0) as absent_days,
                COALESCE(att.leave_days, 0) as leave_days,
                COALESCE(att.late_minutes, 0) as late_minutes,
                COALESCE(att.overtime_minutes, 0) as overtime_minutes,
                COALESCE(att.total_hours, 0) as total_hours,
                att.last_date as last_date
            ')
            ->with(['user', 'details', 'branch', 'jobTitle', 'company']);

        self::applyAccessScope($query);
        self::applyFilters($query, $filters);

        return $query;
    }

    /**
     * Compute KPI stats over every employee within the current filters.
     */
    public static function getStats(array $filters = [], ?array $employeeIds = null): array
    {
        $totals = self::summaryQuery($filters)
            ->when($employeeIds !== null, fn (Builder $query) => $query->whereIn('employees.id', $employeeIds))
            ->toBase()
            ->select([])
            ->selectRaw('COUNT(*) as employees_count,
                SUM(att.recorded_days) as recorded_days, SUM(att.present_days) as present_days,
                SUM(att.late_days) as late_days, SUM(att.absent_days) as absent_days,
                SUM(att.leave_days) as leave_days, SUM(att.late_minutes) as late_minutes,
                SUM(att.total_hours) as total_hours')
            ->first();

        return [
            'employees_count' => (int) $totals->employees_count,
            'recorded_days' => (int) $totals->recorded_days,
            'present_days' => (int) $totals->present_days,
            'late_days' => (int) $totals->late_days,
            'absent_days' => (int) $totals->absent_days,
            'leave_days' => (int) $totals->leave_days,
            'late_minutes' => (int) round((float) $totals->late_minutes),
            'total_hours' => round((float) $totals->total_hours, 2),
        ];
    }

    /**
     * Day-by-day attendance details of a specific employee for a given month.
     */
    public static function getEmployeeMonthDetails(int $employeeId, ?string $month = null): ?array
    {
        $startDate = self::monthStart($month);
        $month = $startDate->format('Y-m');

        $employee = Employee::saudi()->with([
            'user',
            'company',
            'branch',
            'jobTitle',
            'department',
            'details',
        ])->find($employeeId);

        if (! $employee) {
            return null;
        }

        if (! self::canAccessEmployee($employee)) {
            return null;
        }

        $endDate = $startDate->copy()->endOfMonth();

        $records = Attendance::with('device')
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->orderBy('date')
            ->get()
            ->keyBy(fn (Attendance $att) => Carbon::parse($att->date)->format('Y-m-d'));

        $days = [];
        $presentCount = 0;
        $lateCount = 0;
        $absentCount = 0;
        $leaveCount = 0;
        $unrecordedCount = 0;
        $lateMinutes = 0;
        $overtimeMinutes = 0;
        $totalHours = 0.0;

        for ($day = 1; $day <= $endDate->day; $day++) {
            $currentDate = $startDate->copy()->day($day);
            $dateString = $currentDate->format('Y-m-d');
            $record = $records->get($dateString);

            $dayOfWeek = $currentDate->dayOfWeek; // 0 = Sunday, 6 = Saturday
            $dayName = self::$arabicDayNames[$dayOfWeek] ?? $currentDate->format('l');
            $hijriDate = HijriDateService::toHijriNumeric($currentDate);

            if ($record) {
                $status = $record->status instanceof AttendanceStatus
                    ? $record->status
                    : AttendanceStatus::tryFrom((string) $record->status);

                $late = (float) ($record->late_hours ?? 0);
                $overtime = (float) ($record->overtime_hours ?? 0);
                $hours = (float) ($record->total_hours ?? 0);

                if ($status === AttendanceStatus::PRESENT) {
                    $presentCount++;
                } elseif ($status === AttendanceStatus::LATE) {
                    $lateCount++;
                } elseif ($status === AttendanceStatus::ABSENT) {
                    $absentCount++;
                } elseif (in_array($status, [AttendanceStatus::LEAVE, AttendanceStatus::EXCUSED], true)) {
                    $leaveCount++;
                }

                $lateMinutes += $late;
                $overtimeMinutes += $overtime;
                $totalHours += $hours;

                $days[] = [
                    'day_number' => $day,
                    'day_name' => $dayName,
                    'date' => $dateString,
                    'hijri_date' => $hijriDate,
                    'is_weekend' => in_array($dayOfWeek, [5, 6], true), // Friday & Saturday
                    'has_record' => true,
                    'id' => $record->id,
                    'check_in' => $record->check_in ? Carbon::parse($record->check_in)->format('h:i A') : null,
                    'check_out' => $record->check_out ? Carbon::parse($record->check_out)->format('h:i A') : null,
                    'late_minutes' => (int) round($late),
                    'overtime_minutes' => (int) round($overtime),
                    'total_hours' => round($hours, 2),
                    'status' => $status,
                    'status_label' => $status?->getLabel() ?? __('attendance.statuses.present'),
                    'status_color' => $status?->getColor() ?? 'gray',
                    'source_label' => $record->check_in_source?->getLabel() ?? ($record->check_in_source ? (string) $record->check_in_source : null),
                    'device_name' => $record->device?->name,
                    'notes' => $record->notes,
                ];
            } else {
                $unrecordedCount++;

                $days[] = [
                    'day_number' => $day,
                    'day_name' => $dayName,
                    'date' => $dateString,
                    'hijri_date' => $hijriDate,
                    'is_weekend' => in_array($dayOfWeek, [5, 6], true),
                    'has_record' => false,
                    'id' => null,
                    'check_in' => null,
                    'check_out' => null,
                    'late_minutes' => 0,
                    'overtime_minutes' => 0,
                    'total_hours' => 0.0,
                    'status' => null,
                    'status_label' => __('attendance.summary.modal.unrecorded'),
                    'status_color' => 'gray',
                    'source_label' => null,
                    'device_name' => null,
                    'notes' => null,
                ];
            }
        }

        $monthCarbon = $startDate;
        $monthLabel = (self::$arabicMonthNames[$monthCarbon->month] ?? $monthCarbon->format('F')).' '.$monthCarbon->year;
        $monthHijri = HijriDateService::toHijri($startDate);

        return [
            'employee' => $employee,
            'month' => $month,
            'month_label' => $monthLabel,
            'month_hijri' => $monthHijri,
            'days' => $days,
            'stats' => [
                'days_in_month' => $endDate->day,
                'recorded_days' => $records->count(),
                'present_days' => $presentCount,
                'late_days' => $lateCount,
                'absent_days' => $absentCount,
                'leave_days' => $leaveCount,
                'unrecorded_days' => $unrecordedCount,
                'late_minutes' => (int) round($lateMinutes),
                'overtime_minutes' => (int) round($overtimeMinutes),
                'total_hours' => round($totalHours, 2),
            ],
        ];
    }

    /**
     * Unpaginated summary rows for CSV export.
     */
    public static function exportRows(array $filters = []): Collection
    {
        return self::summaryQuery($filters)
            ->orderBy('absent_days', 'desc')
            ->orderBy('late_minutes', 'desc')
            ->orderBy('employees.id')
            ->get();
    }

    /**
     * Return select options for months (e.g. ['2026-11' => 'نوفمبر 2026']).
     */
    public static function monthOptions(int $monthsBack = 24): array
    {
        $options = [];
        $cursor = now()->startOfMonth();

        for ($i = 0; $i < $monthsBack; $i++) {
            $key = $cursor->format('Y-m');
            $label = (self::$arabicMonthNames[$cursor->month] ?? $cursor->format('F')).' '.$cursor->year;
            $options[$key] = $label;
            $cursor->subMonth();
        }

        return $options;
    }

    /**
     * Extract date range from filters.
     *
     * @return array{0: string, 1: string}
     */
    public static function resolveDateRange(array $filters): array
    {
        $month = $filters['month'] ?? $filters['period'] ?? null;

        if ($month) {
            $monthStart = self::monthStart($month);
            $start = $monthStart->format('Y-m-d');
            $end = $monthStart->copy()->endOfMonth()->format('Y-m-d');

            return [$start, $end];
        }

        if (! empty($filters['date_from']) && ! empty($filters['date_to'])) {
            return [$filters['date_from'], $filters['date_to']];
        }

        $now = now();

        return [
            $now->copy()->startOfMonth()->format('Y-m-d'),
            $now->copy()->endOfMonth()->format('Y-m-d'),
        ];
    }

    /**
     * Company and branch permission scoping directly on employees table.
     */
    public static function applyAccessScope(Builder $query): Builder
    {
        return CompanyAccessScopeService::apply($query);
    }

    /**
     * Verify whether the authenticated user has access to a specific employee.
     */
    public static function canAccessEmployee(Employee $employee): bool
    {
        return self::applyAccessScope(Employee::query())->whereKey($employee->getKey())->exists();
    }

    public static function monthStart(?string $month): Carbon
    {
        if (! $month || ! preg_match('/^(?!0000)\d{4}-(0[1-9]|1[0-2])$/D', $month)) {
            return now()->startOfMonth();
        }

        return Carbon::createFromFormat('!Y-m', $month);
    }

    /**
     * Apply filter bar criteria to the employees query.
     */
    protected static function applyFilters(Builder $query, array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $group) use ($search) {
                $group->where('employees.employee_number', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('details', fn ($dq) => $dq->where('national_id', 'like', "%{$search}%")
                        ->orWhere('iqama_number', 'like', "%{$search}%")
                    );
            });
        }

        if (! empty($filters['company_id'])) {
            $companyIds = (array) $filters['company_id'];
            $companyIds = array_filter($companyIds, fn ($id) => $id !== null && $id !== '' && $id !== 'all');
            if (! empty($companyIds)) {
                $query->whereIn('employees.company_id', $companyIds);
            }
        }

        if (! empty($filters['branch_id'])) {
            $branchIds = (array) $filters['branch_id'];
            $branchIds = array_filter($branchIds, fn ($id) => $id !== null && $id !== '' && $id !== 'all');
            if (! empty($branchIds)) {
                $query->whereIn('employees.branch_id', $branchIds);
            }
        }

        $employmentStatus = $filters['employment_status'] ?? 'all';
        if ($employmentStatus === 'active') {
            $query->where('employees.status', GeneralStatus::ACTIVE);
        } elseif ($employmentStatus === 'inactive') {
            $query->where('employees.status', '!=', GeneralStatus::ACTIVE);
        }

        return $query;
    }
}
