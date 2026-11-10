<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Services\AttendanceSummaryService;
use Illuminate\Http\Request;

class AttendancePrintController extends Controller
{
    public function summary(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->can('viewAny', Attendance::class), 403);

        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'search' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'array'],
            'company_id.*' => ['integer'],
            'branch_id' => ['nullable', 'array'],
            'branch_id.*' => ['integer'],
            'employment_status' => ['nullable', 'in:all,active,inactive'],
        ]);

        $query = AttendanceSummaryService::summaryQuery($filters);

        return view('attendance.summary-print', [
            'rows' => $query->orderBy('absent_days', 'desc')->orderBy('late_minutes', 'desc')->orderBy('employees.id')->get(),
            'dateRange' => AttendanceSummaryService::resolveDateRange($filters),
        ]);
    }

    /**
     * Print monthly attendance sheet for an employee.
     */
    public function sheet(Employee $employee, Request $request)
    {
        if (! auth()->user()?->can('viewAny', Attendance::class)) {
            abort(403);
        }

        if (! AttendanceSummaryService::canAccessEmployee($employee)) {
            abort(403);
        }

        $month = $request->query('month', now()->format('Y-m'));
        $details = AttendanceSummaryService::getEmployeeMonthDetails($employee->id, $month);

        if (! $details) {
            abort(404);
        }

        return view('attendance.employee-sheet-print', [
            'details' => $details,
            'employee' => $employee,
            'month' => $month,
        ]);
    }
}
