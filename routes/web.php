<?php

use App\Http\Controllers\DebtSummaryPrintController;
use App\Http\Controllers\OfferPdfController;

use Illuminate\Support\Facades\Route;

// ─── System Documentation & User Guide ───────────────────────────────────
Route::get('/docs', [\App\Http\Controllers\DocumentationController::class, 'index'])->name('documentation.index');
Route::get('/documentation', [\App\Http\Controllers\DocumentationController::class, 'index']);

// ─── Offer PDF & Treasury & Contract Vouchers & Leaves ────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/offers/{offer}/pdf', [OfferPdfController::class, 'download'])->name('offers.pdf');
    Route::get('/offers/{offer}/print', [OfferPdfController::class, 'preview'])->name('offers.print');
    Route::get('/treasury/{transaction}/print', [\App\Http\Controllers\ExportPdfController::class, 'printTreasuryVoucher'])->name('treasury.voucher.print');

    // Centralized PDF Export Routes
    Route::get('/exports/violations/{section}', [\App\Http\Controllers\ExportPdfController::class, 'exportViolations'])->name('exports.violations');
    Route::get('/exports/violation-statement', [\App\Http\Controllers\ExportPdfController::class, 'exportViolationStatement'])->name('exports.violation-statement');
    Route::get('/exports/violation-voucher/{transaction}', [\App\Http\Controllers\ExportPdfController::class, 'printViolationVoucher'])->name('exports.violation-voucher');
    Route::get('/exports/treasury-statement', [\App\Http\Controllers\ExportPdfController::class, 'exportTreasuryStatement'])->name('exports.treasury-statement');
    Route::get('/exports/treasury-voucher/{transaction}', [\App\Http\Controllers\ExportPdfController::class, 'printTreasuryVoucher'])->name('exports.treasury-voucher');
    Route::get('/treasury/{transaction}/print', [\App\Http\Controllers\TreasuryVoucherController::class, 'print'])->name('treasury.voucher.print');
    Route::get('/leaves/summary/print', [\App\Http\Controllers\LeavePrintController::class, 'summary'])->name('leaves.summary.print');
    Route::get('/leaves/{employee}/statement/print', [\App\Http\Controllers\LeavePrintController::class, 'statement'])->name('leaves.statement.print');
    Route::get('/leaves/{leave}/print', [\App\Http\Controllers\LeavePrintController::class, 'print'])->name('leaves.print');
    Route::get('/leaves-report/print', [\App\Http\Controllers\LeavePrintController::class, 'printReport'])->name('leaves.report.print');
    Route::get('/contract-vouchers/{voucher}/print', [\App\Http\Controllers\ContractVoucherPrintController::class, 'print'])->name('contract_vouchers.print');
    Route::get('/contract-vouchers/{voucher}/pdf', [\App\Http\Controllers\ContractVoucherPrintController::class, 'pdf'])->name('contract_vouchers.pdf');

    // Official Letters Print & Preview Routes
    Route::get('/official-letters/{letter}/print', [\App\Http\Controllers\OfficialLetterPrintController::class, 'print'])->name('official_letters.print');
    Route::get('/official-letters/{letter}/pdf', [\App\Http\Controllers\OfficialLetterPrintController::class, 'pdf'])->name('official_letters.pdf');

    // Debts PDF Export & Print Routes
    Route::get('/debts/summary/print/{debt?}', DebtSummaryPrintController::class)
        ->whereNumber('debt')->name('debts.summary.print');
    Route::get('/exports/debts-statement', [\App\Http\Controllers\ExportPdfController::class, 'exportDebtsStatement'])->name('exports.debts-statement');
    Route::get('/exports/debt-voucher/{debt}', [\App\Http\Controllers\ExportPdfController::class, 'printDebtVoucher'])->name('exports.debt-voucher');

    // Employees & Owners Statement Export & Print Route
    Route::get('/exports/employees-statement', [\App\Http\Controllers\ExportPdfController::class, 'exportEmployeesStatement'])->name('exports.employees-statement');

    // Invoices Print & PDF Routes
    Route::get('/exports/invoices-statement', [\App\Http\Controllers\ExportPdfController::class, 'exportInvoicesStatement'])->name('exports.invoices-statement');
    Route::get('/invoices/{invoice}/print', [\App\Http\Controllers\InvoicePrintController::class, 'print'])->name('invoices.print');
    Route::get('/invoices/{invoice}/pdf', [\App\Http\Controllers\InvoicePrintController::class, 'pdf'])->name('invoices.pdf');

    // Supervision Shifts Print & PDF Routes
    Route::get('/exports/supervision-shifts-statement', [\App\Http\Controllers\SupervisionShiftPrintController::class, 'statement'])->name('exports.supervision-shifts-statement');
    Route::get('/supervision-shifts/{shift}/print', [\App\Http\Controllers\SupervisionShiftPrintController::class, 'print'])->name('supervision-shifts.print');
    Route::get('/supervision-shifts/{shift}/pdf', [\App\Http\Controllers\SupervisionShiftPrintController::class, 'pdf'])->name('supervision-shifts.pdf');

    // Universal Dynamic Print Route
    Route::get('/print/{type}/{id}', [\App\Http\Controllers\UniversalPrintController::class, 'print'])->name('system.print');

    // Individual Payroll Records Print & PDF Routes
    Route::get('/payroll-records/{record}/print', [\App\Http\Controllers\PayrollRecordPrintController::class, 'print'])->name('payroll-records.print');
    Route::get('/payroll-records/{record}/pdf', [\App\Http\Controllers\PayrollRecordPrintController::class, 'pdf'])->name('payroll-records.pdf');

    // Attendance Employee Monthly Sheet Print Route
    Route::get('/attendances/summary/print', [\App\Http\Controllers\AttendancePrintController::class, 'summary'])->name('attendance.summary.print');
    Route::get('/attendances/{employee}/sheet/print', [\App\Http\Controllers\AttendancePrintController::class, 'sheet'])->name('attendance.sheet.print');

    // Asset Fund Statement PDF
    Route::get('/asset-fund-statement/pdf', [\App\Http\Controllers\AssetFundStatementController::class, 'pdf'])->name('asset-fund.statement.pdf');

    // Operations Workflow Permissions Sync Route
    Route::get('/system/sync-operations-permissions', function () {
        $result = \App\Services\OperationsPermissionService::syncPermissions();

        return response()->json([
            'status' => 'success',
            'message' => __('services_tracking.permissions.sync_success'),
            'details' => $result,
        ]);
    })->name('system.sync-operations-permissions');

    // Operations Demo Seeder Route (for automated browser testing)
    Route::get('/system/seed-operations-demo', function () {
        $seeder = new \Database\Seeders\OperationsDemoSeeder;
        $result = $seeder->run();

        return response()->json([
            'status' => 'success',
            'message' => 'Operations demo data seeded successfully',
            'data' => $result,
        ]);
    })->name('system.seed-operations-demo');
});

// Also allow accessible invocation via GET for administrators
Route::get('/sync-operations-permissions', function () {
    (new \Database\Seeders\RolesAndPermissionsSeeder)->run();
    $result = \App\Services\OperationsPermissionService::syncPermissions();

    return response()->json([
        'status' => 'success',
        'message' => 'All system roles and operations permissions seeded successfully',
        'details' => $result,
    ]);
});

Route::get('/seed-operations-demo', function () {
    $seeder = new \Database\Seeders\OperationsDemoSeeder;
    $result = $seeder->run();

    return response()->json([
        'status' => 'success',
        'message' => 'Operations demo data seeded successfully',
        'data' => $result,
    ]);
});

Route::get('/test-notification', function () {
    $user = auth()->user()
        ?? \App\Models\User::where('email', 'finance@orcal.test')->first()
        ?? \App\Models\User::where('email', 'admin@admin.com')->first();

    if (! $user) {
        return response()->json(['status' => 'error', 'message' => 'No user found']);
    }

    \Filament\Notifications\Notification::make()
        ->title(__('services_tracking.notifications.test_title'))
        ->body(__('services_tracking.notifications.test_body'))
        ->icon('heroicon-o-bell-alert')
        ->status('success')
        ->sendToDatabase($user);

    return response()->json([
        'status' => 'success',
        'message' => 'Test notification sent to user: '.$user->email,
        'user_id' => $user->id,
    ]);
});

// ─── ZKTeco Biometric ADMS / Push Protocol Routes ───────────────────────────
Route::prefix('iclock')->group(function () {
    Route::get('/cdata', [\App\Http\Controllers\Api\ZkAdmsController::class, 'handshake']);
    Route::post('/cdata', [\App\Http\Controllers\Api\ZkAdmsController::class, 'receiveData']);
    Route::get('/getrequest', [\App\Http\Controllers\Api\ZkAdmsController::class, 'getRequest']);
    Route::post('/devicecmd', [\App\Http\Controllers\Api\ZkAdmsController::class, 'deviceCommand']);
    Route::match(['get', 'post'], '/registry', [\App\Http\Controllers\Api\ZkAdmsController::class, 'registry']);
});
