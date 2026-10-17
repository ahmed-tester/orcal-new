<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Leave;
use App\Models\LeaveType;
use App\Services\LeaveBalanceService;
use Illuminate\Http\Request;

class LeavePrintController extends Controller
{
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
