<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\TaskAssignment;
use App\Services\CompanyAccessScopeService;
use App\Services\TaskAssignmentSummaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskAssignmentPrintController extends Controller
{
    /**
     * Print the full task-assignment summary (distributor view).
     *
     * Supports filtering by:
     *   - employee_ids[] : specific employee IDs (optional)
     *   - date_from / date_to : or the month shorthand
     *   - company_id[], branch_id[]
     */
    public function summary(Request $request)
    {
        Gate::authorize('viewAny', TaskAssignment::class);

        $filters = $request->validate([
            'employee_ids'   => ['nullable', 'array'],
            'employee_ids.*' => ['integer'],
            'date_from'      => ['nullable', 'date'],
            'date_to'        => ['nullable', 'date'],
            'month'          => ['nullable', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'company_id'     => ['nullable', 'array'],
            'company_id.*'   => ['integer'],
            'branch_id'      => ['nullable', 'array'],
            'branch_id.*'    => ['integer'],
            'search'         => ['nullable', 'string', 'max:255'],
        ]);

        $query = TaskAssignmentSummaryService::summaryQuery($filters);

        // If specific employees requested, narrow down
        if (! empty($filters['employee_ids'])) {
            $query->whereIn('employees.id', $filters['employee_ids']);
        }

        $rows = $query->orderBy('employees.id')->get();

        $stats = TaskAssignmentSummaryService::stats($filters);

        [$dateFrom, $dateTo] = TaskAssignmentSummaryService::resolveDateRange($filters);

        return view('task-assignments.print-summary', [
            'rows'     => $rows,
            'stats'    => $stats,
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
            'filters'  => $filters,
        ]);
    }

    /**
     * Print a single employee's task statement (employee self-service or distributor).
     *
     * Accessible by:
     *   - The employee themselves (from my-tasks)
     *   - Anyone with viewAny TaskAssignment (distributor / manager)
     */
    public function statement(Employee $employee, Request $request)
    {
        $user = $request->user();
        abort_if(! $user, 403);

        $isOwn   = (int) $employee->user_id === (int) $user->id;
        $canView = $user->can('viewAny', TaskAssignment::class);

        abort_unless($isOwn || $canView, 403);

        // Make sure the employee is within the authorized scope when it's not own
        if (! $isOwn && ! TaskAssignmentSummaryService::canAccessEmployee($employee)) {
            abort(403);
        }

        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
            'month'     => ['nullable', 'string', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $details = TaskAssignmentSummaryService::employeeDetails($employee->id, $filters);

        [$dateFrom, $dateTo] = TaskAssignmentSummaryService::resolveDateRange($filters);

        return view('task-assignments.print-statement', [
            'employee' => $details['employee'],
            'items'    => $details['items'],
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
        ]);
    }
}
