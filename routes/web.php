<?php

use App\Http\Controllers\Api\ZkAdmsController;
use App\Http\Controllers\AssetFundStatementController;
use App\Http\Controllers\AttendancePrintController;
use App\Http\Controllers\CompanyPrintController;
use App\Http\Controllers\ContractVoucherPrintController;
use App\Http\Controllers\DailyJournalSummaryPrintController;
use App\Http\Controllers\DebtSummaryPrintController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\ExportPdfController;
use App\Http\Controllers\InvoicePrintController;
use App\Http\Controllers\LeavePrintController;
use App\Http\Controllers\OfferPdfController;
use App\Http\Controllers\OfficialLetterPrintController;
use App\Http\Controllers\PayrollRecordPrintController;
use App\Http\Controllers\SupervisionShiftPrintController;
use App\Http\Controllers\TreasuryVoucherController;
use App\Http\Controllers\UniversalPrintController;
use App\Http\Controllers\ViolationSummaryPrintController;
use Illuminate\Support\Facades\Route;

// ─── System Documentation & User Guide ───────────────────────────────────
Route::get('/login-redirect', fn () => redirect()->route('filament.admin.auth.login'))->name('login');
Route::get('/docs', [DocumentationController::class, 'index'])->name('documentation.index');
Route::get('/documentation', [DocumentationController::class, 'index']);

// ─── Offer PDF & Treasury & Contract Vouchers & Leaves ────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/offers/{offer}/pdf', [OfferPdfController::class, 'download'])->name('offers.pdf');
    Route::get('/offers/{offer}/print', [OfferPdfController::class, 'preview'])->name('offers.print');

    // Centralized PDF Export Routes
    Route::get('/exports/violations/{section}', [ExportPdfController::class, 'exportViolations'])->name('exports.violations');
    Route::get('/exports/violation-statement', [ExportPdfController::class, 'exportViolationStatement'])->name('exports.violation-statement');
    Route::get('/exports/violation-voucher/{transaction}', [ExportPdfController::class, 'printViolationVoucher'])->name('exports.violation-voucher');
    Route::get('/exports/treasury-statement', [ExportPdfController::class, 'exportTreasuryStatement'])->name('exports.treasury-statement');
    Route::get('/exports/treasury-voucher/{transaction}', [ExportPdfController::class, 'printTreasuryVoucher'])->name('exports.treasury-voucher');
    Route::get('/treasury/{transaction}/print', [TreasuryVoucherController::class, 'print'])->name('treasury.voucher.print');
    Route::get('/leaves/summary/print', [LeavePrintController::class, 'summary'])->name('leaves.summary.print');
    Route::get('/leaves/{employee}/statement/print', [LeavePrintController::class, 'statement'])->name('leaves.statement.print');
    Route::get('/leaves/{leave}/print', [LeavePrintController::class, 'print'])->name('leaves.print');
    Route::get('/leaves-report/print', [LeavePrintController::class, 'printReport'])->name('leaves.report.print');
    Route::get('/contract-vouchers/{voucher}/print', [ContractVoucherPrintController::class, 'print'])->name('contract_vouchers.print');
    Route::get('/contract-vouchers/{voucher}/pdf', [ContractVoucherPrintController::class, 'pdf'])->name('contract_vouchers.pdf');

    // Official Letters Print & Preview Routes
    Route::get('/official-letters/{letter}/print', [OfficialLetterPrintController::class, 'print'])->name('official_letters.print');
    Route::get('/official-letters/{letter}/pdf', [OfficialLetterPrintController::class, 'pdf'])->name('official_letters.pdf');

    // Violations PDF Export & Print Routes
    Route::get('/violations/summary/print/{company?}', ViolationSummaryPrintController::class)
        ->whereNumber('company')->name('violations.summary.print');

    // Debts PDF Export & Print Routes
    Route::get('/debts/summary/print/{debt?}', DebtSummaryPrintController::class)
        ->whereNumber('debt')->name('debts.summary.print');
    Route::get('/exports/debts-statement', [ExportPdfController::class, 'exportDebtsStatement'])->name('exports.debts-statement');
    Route::get('/exports/debt-voucher/{debt}', [ExportPdfController::class, 'printDebtVoucher'])->name('exports.debt-voucher');

    // Daily Journals Summary Print Route
    Route::get('/daily-journals/summary/print/{journal?}', DailyJournalSummaryPrintController::class)
        ->whereNumber('journal')->name('daily-journals.summary.print');

    // Employees & Owners Statement Export & Print Route
    Route::get('/exports/employees-statement', [ExportPdfController::class, 'exportEmployeesStatement'])->name('exports.employees-statement');

    // Invoices Print & PDF Routes
    Route::get('/exports/invoices-statement', [ExportPdfController::class, 'exportInvoicesStatement'])->name('exports.invoices-statement');
    Route::get('/invoices/{invoice}/print', [InvoicePrintController::class, 'print'])->name('invoices.print');
    Route::get('/invoices/{invoice}/pdf', [InvoicePrintController::class, 'pdf'])->name('invoices.pdf');

    // Supervision Shifts Print & PDF Routes
    Route::get('/exports/supervision-shifts-statement', [SupervisionShiftPrintController::class, 'statement'])->name('exports.supervision-shifts-statement');
    Route::get('/supervision-shifts/{shift}/print', [SupervisionShiftPrintController::class, 'print'])->name('supervision-shifts.print');
    Route::get('/supervision-shifts/{shift}/pdf', [SupervisionShiftPrintController::class, 'pdf'])->name('supervision-shifts.pdf');

    // Universal Dynamic Print Route
    Route::get('/print/{type}/{id}', [UniversalPrintController::class, 'print'])->name('system.print');

    // Individual Payroll Records Print & PDF Routes
    Route::get('/payroll-records/{record}/print', [PayrollRecordPrintController::class, 'print'])->name('payroll-records.print');
    Route::get('/payroll-records/{record}/pdf', [PayrollRecordPrintController::class, 'pdf'])->name('payroll-records.pdf');

    // Attendance Employee Monthly Sheet Print Route
    Route::get('/attendances/summary/print', [AttendancePrintController::class, 'summary'])->name('attendance.summary.print');
    Route::get('/attendances/saudi-period/print', [AttendancePrintController::class, 'saudiPeriod'])->name('attendance.saudi-period.print');
    Route::get('/attendances/{employee}/sheet/print', [AttendancePrintController::class, 'sheet'])->name('attendance.sheet.print');

    // Asset Fund Statement PDF
    Route::get('/asset-fund-statement/pdf', [AssetFundStatementController::class, 'pdf'])->name('asset-fund.statement.pdf');

    // Task Assignments Print Routes
    Route::get('/task-assignments/summary/print', [\App\Http\Controllers\TaskAssignmentPrintController::class, 'summary'])->name('task-assignments.summary.print');
    Route::get('/task-assignments/{employee}/statement/print', [\App\Http\Controllers\TaskAssignmentPrintController::class, 'statement'])->name('task-assignments.statement.print')->whereNumber('employee');

    // Companies Print & PDF Routes
    Route::get('/companies/statement/print', [CompanyPrintController::class, 'statement'])->name('companies.statement.print');
    Route::get('/exports/companies-statement', [CompanyPrintController::class, 'statement'])->name('exports.companies-statement');
    Route::get('/companies/{company}/print', [CompanyPrintController::class, 'print'])->name('companies.print');
    Route::get('/companies/{company}/pdf', [CompanyPrintController::class, 'pdf'])->name('companies.pdf');
});

// ─── ZKTeco Biometric ADMS / Push Protocol Routes ───────────────────────────
Route::prefix('iclock')->group(function () {
    Route::get('/cdata', [ZkAdmsController::class, 'handshake']);
    Route::post('/cdata', [ZkAdmsController::class, 'receiveData']);
    Route::get('/getrequest', [ZkAdmsController::class, 'getRequest']);
    Route::post('/devicecmd', [ZkAdmsController::class, 'deviceCommand']);
    Route::match(['get', 'post'], '/registry', [ZkAdmsController::class, 'registry']);
});
