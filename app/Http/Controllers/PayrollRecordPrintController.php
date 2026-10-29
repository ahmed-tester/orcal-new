<?php

namespace App\Http\Controllers;

use App\Models\PayrollRecord;
use App\Services\ExportPdfService;
use Illuminate\Http\Request;

class PayrollRecordPrintController extends Controller
{
    public function print(PayrollRecord $record)
    {
        abort_unless(auth()->user()?->can('view', $record), 403);

        $record->load([
            'company',
            'employee.user',
            'employee.details',
            'employee.jobTitle',
            'employee.branch',
            'creator',
            'payments.treasuryTransaction',
        ]);

        return view('payroll-records.print', compact('record'));
    }

    public function pdf(Request $request, PayrollRecord $record)
    {
        abort_unless(auth()->user()?->can('view', $record), 403);

        $record->load([
            'company',
            'employee.user',
            'employee.details',
            'employee.jobTitle',
            'employee.branch',
            'creator',
            'payments.treasuryTransaction',
        ]);

        $html = view('payroll-records.print', compact('record'))->render();
        $filename = 'payroll-slip-' . ($record->record_number ?: $record->id) . '.pdf';

        return ExportPdfService::renderHtml($html, $filename, true, [
            'format' => 'A4',
            'orientation' => 'L',
            'margin_top' => 8,
            'margin_bottom' => 8,
            'margin_left' => 10,
            'margin_right' => 10,
        ]);
    }
}

