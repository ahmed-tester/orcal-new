<?php

namespace App\Services\Reports;

use App\Services\Reports\Contracts\ReportInterface;
use App\Services\Reports\Types\AttendanceDevicesReport;
use App\Services\Reports\Types\AttendanceReport;
use App\Services\Reports\Types\BranchesReport;
use App\Services\Reports\Types\CompaniesReport;
use App\Services\Reports\Types\CustomersReport;
use App\Services\Reports\Types\DailyJournalsReport;
use App\Services\Reports\Types\DepartmentsReport;
use App\Services\Reports\Types\EmployeeRewardsReport;
use App\Services\Reports\Types\EmployeesReport;
use App\Services\Reports\Types\FaqsReport;
use App\Services\Reports\Types\GuarantorsReport;
use App\Services\Reports\Types\InstallmentPaymentsReport;
use App\Services\Reports\Types\InstallmentsReport;
use App\Services\Reports\Types\InvoicesReport;
use App\Services\Reports\Types\InvoiceTypesReport;
use App\Services\Reports\Types\JobTitlesReport;
use App\Services\Reports\Types\LeavesReport;
use App\Services\Reports\Types\LeaveTypesReport;
use App\Services\Reports\Types\LoanFundLoanPaymentsReport;
use App\Services\Reports\Types\LoanFundLoansReport;
use App\Services\Reports\Types\LoanFundsReport;
use App\Services\Reports\Types\OffersReport;
use App\Services\Reports\Types\OwnersReport;
use App\Services\Reports\Types\PaymentCollectionItemsReport;
use App\Services\Reports\Types\PayrollRecordsReport;
use App\Services\Reports\Types\PosDevicesReport;
use App\Services\Reports\Types\RoscaFundPaymentsReport;
use App\Services\Reports\Types\RoscaFundsReport;
use App\Services\Reports\Types\ServicesReport;
use App\Services\Reports\Types\TaskAssignmentsReport;
use App\Services\Reports\Types\TasksReport;
use App\Services\Reports\Types\TicketsReport;
use App\Services\Reports\Types\TreasuryTransactionsReport;
use App\Services\Reports\Types\UsersReport;
use App\Services\Reports\Types\ViolationsReport;
use App\Services\Reports\Types\ViolationTransactionsReport;
use App\Services\Reports\Types\WeeklyNotesReport;

class GeneralReportService
{
    /**
     * Map of available report classes.
     *
     * @var array<string, class-string<ReportInterface>>
     */
    protected static array $reportMap = [
        'users' => UsersReport::class,
        'employees' => EmployeesReport::class,
        'departments' => DepartmentsReport::class,
        'attendance' => AttendanceReport::class,
        'attendance_devices' => AttendanceDevicesReport::class,
        'leaves' => LeavesReport::class,
        'leave_types' => LeaveTypesReport::class,
        'payroll_records' => PayrollRecordsReport::class,
        'employee_rewards' => EmployeeRewardsReport::class,
        'treasury_transactions' => TreasuryTransactionsReport::class,
        'daily_journals' => DailyJournalsReport::class,
        'invoices' => InvoicesReport::class,
        'invoice_types' => InvoiceTypesReport::class,
        'installments' => InstallmentsReport::class,
        'installment_payments' => InstallmentPaymentsReport::class,
        'payment_collection_items' => PaymentCollectionItemsReport::class,
        'loan_funds' => LoanFundsReport::class,
        'loan_fund_loans' => LoanFundLoansReport::class,
        'loan_fund_loan_payments' => LoanFundLoanPaymentsReport::class,
        'rosca_funds' => RoscaFundsReport::class,
        'rosca_fund_payments' => RoscaFundPaymentsReport::class,
        'tickets' => TicketsReport::class,
        'weekly_notes' => WeeklyNotesReport::class,
        'tasks' => TasksReport::class,
        'task_assignments' => TaskAssignmentsReport::class,
        'offers' => OffersReport::class,
        'customers' => CustomersReport::class,
        'owners' => OwnersReport::class,
        'companies' => CompaniesReport::class,
        'branches' => BranchesReport::class,
        'pos_devices' => PosDevicesReport::class,
        'services' => ServicesReport::class,
        'service_transactions' => \App\Services\Reports\Types\ServiceTransactionsReport::class,
        'job_titles' => JobTitlesReport::class,
        'violations' => ViolationsReport::class,
        'violation_transactions' => ViolationTransactionsReport::class,
        'faqs' => FaqsReport::class,
        'guarantors' => GuarantorsReport::class,
    ];

    /**
     * Cached report instances to avoid re-creation.
     *
     * @var array<string, ReportInterface>
     */
    protected static array $instances = [];

    /**
     * Resolve report handler instance by type (cached).
     */
    public static function getReport(string $reportType): ReportInterface
    {
        return static::$instances[$reportType] ??= new (static::$reportMap[$reportType] ?? UsersReport::class)();
    }

    /**
     * Get available report types list with metadata.
     */
    public static function getAvailableReports(): array
    {
        $reports = [];
        foreach (static::$reportMap as $key => $class) {
            $instance = static::getReport($key);
            $reports[$key] = [
                'title' => $instance->getTitle(),
                'label' => $instance->getLabel(),
                'icon' => $instance->getIcon(),
                'export_filename' => $instance->getExportFilename(),
            ];
        }
        return $reports;
    }

    /**
     * Get dynamic stats for the given report type and date range.
     */
    public static function getStats(string $reportType, ?string $dateFrom = null, ?string $dateTo = null, ?int $branchId = null, ?int $guarantorId = null): array
    {
        $report = static::getReport($reportType);
        if ($report instanceof GuarantorsReport) {
            return $report->getStats($dateFrom, $dateTo, $branchId, $guarantorId);
        }
        return $report->getStats($dateFrom, $dateTo, $branchId);
    }

    /**
     * Build the query for the selected report based on filters.
     */
    public static function getQuery(string $reportType, ?string $dateFrom, ?string $dateTo, ?int $branchId, string $status, ?string $search, ?int $guarantorId = null)
    {
        $report = static::getReport($reportType);
        if ($report instanceof GuarantorsReport) {
            return $report->getQuery($dateFrom, $dateTo, $branchId, $status, $search, $guarantorId);
        }
        return $report->getQuery($dateFrom, $dateTo, $branchId, $status, $search);
    }

    /**
     * Get sortable column mappings (display key => DB column).
     */
    public static function getSortableColumns(string $reportType): array
    {
        return static::getReport($reportType)->getSortableColumns();
    }

    /**
     * Get table column definitions for the report.
     */
    public static function getColumns(string $reportType): array
    {
        return static::getReport($reportType)->getColumns();
    }

    /**
     * Map model item to structured key-value for display and export.
     */
    public static function formatRow(string $reportType, $item, bool $isCsv = false): array
    {
        return static::getReport($reportType)->formatRow($item, $isCsv);
    }
}
