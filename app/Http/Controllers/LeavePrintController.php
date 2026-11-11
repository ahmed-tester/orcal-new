<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Services\LeaveBalanceService;
use App\Services\LeaveSummaryService;
use Illuminate\Http\Request;

class LeavePrintController extends Controller
{
    /**
     * Print summary of leaves for all employees matching filters.
     */
    public function summary(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->can('viewAny', Leave::class), 403);

        $filters = $request->validate([
            'year' => ['nullable', 'regex:/^(all|[0-9]{4})$/'],
            'search' => ['nullable', 'string', 'max:255'],
            'company_id' => ['nullable', 'array'],
            'company_id.*' => ['integer'],
            'branch_id' => ['nullable', 'array'],
            'branch_id.*' => ['integer'],
            'employment_status' => ['nullable', 'in:all,active,inactive'],
            'leave_filter' => ['nullable', 'in:all,on_leave,has_pending'],
        ]);

        $year = LeaveSummaryService::resolveYear(array_key_exists('year', $filters) ? $filters['year'] : now()->year);
        $filters['year'] = $year ?? 'all';
        $query = LeaveSummaryService::summaryQuery($filters);
        $rows = $query->orderBy('is_on_leave_today', 'desc')
            ->orderBy('pending_leaves_count', 'desc')
            ->orderBy('employees.id')
            ->get();

        $stats = LeaveSummaryService::getStats($filters);

        return view('leaves.summary-print', [
            'rows' => $rows,
            'stats' => $stats,
            'year' => $year,
        ]);
    }

    /**
     * Print leave statement and balance sheet for an employee.
     */
    public function statement(Employee $employee, Request $request)
    {
        $user = $request->user();
        abort_unless($user?->can('viewAny', Leave::class), 403);

        if (! LeaveSummaryService::canAccessEmployee($employee)) {
            abort(403);
        }

        $request->validate(['year' => ['nullable', 'regex:/^(all|[0-9]{4})$/']]);
        $year = LeaveSummaryService::resolveYear($request->query('year', now()->year));
        $details = LeaveSummaryService::getEmployeeLeaveDetails($employee->id, $year);

        if (! $details) {
            abort(404);
        }

        return view('leaves.employee-statement-print', [
            'details' => $details,
            'employee' => $employee,
            'year' => $year,
        ]);
    }

    /**
     * Print single leave document / voucher.
     */
    public function print(Leave $leave)
    {
        $leave->load([
            'employee.user',
            'employee.details',
            'employee.company',
            'employee.branch',
            'employee.department',
            'employee.jobTitle',
            'leaveType',
            'approver',
        ]);

        $balance = LeaveBalanceService::calculateBalance(
            $leave->employee,
            $leave->leave_type_id,
            $leave->id,
            $leave->start_date
        );

        return view('leaves.print', compact('leave', 'balance'));
    }

    /**
     * Print leaves statement / report for a date range.
     */
    public function printReport(Request $request)
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $companyId = $request->query('company_id');
        $leaveTypeId = $request->query('leave_type_id');
        $status = $request->query('status', 'all');

        $query = Leave::with([
            'employee.user',
            'employee.details',
            'employee.company',
            'employee.branch',
            'leaveType',
        ]);

        if ($dateFrom) {
            $query->whereDate('start_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('end_date', '<=', $dateTo);
        }
        if ($companyId) {
            $query->whereHas('employee', fn ($q) => $q->where('company_id', $companyId));
        }
        if ($leaveTypeId) {
            $query->where('leave_type_id', $leaveTypeId);
        }
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $leaves = $query->orderBy('start_date', 'asc')->get();

        $selectedCompany = $companyId ? Company::find($companyId) : null;
        $selectedLeaveType = $leaveTypeId ? LeaveType::find($leaveTypeId) : null;

        return view('leaves.report-print', compact(
            'leaves',
            'dateFrom',
            'dateTo',
            'selectedCompany',
            'selectedLeaveType',
            'status'
        ));
    }
}
