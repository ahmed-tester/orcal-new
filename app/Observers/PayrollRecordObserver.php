<?php

namespace App\Observers;

use App\Models\PayrollRecord;
use App\Services\CodeGeneratorService;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;

class PayrollRecordObserver
{
    /**
     * Handle the PayrollRecord "creating" event.
     */
    public function creating(PayrollRecord $record): void
    {
        if (empty($record->record_number)) {
            $record->record_number = CodeGeneratorService::generate(
                modelClass: PayrollRecord::class,
                column: 'record_number',
                prefix: 'P',
                companyId: $record->company_id,
                padLength: 4,
                datePart: 'Ym',
            );
        }

        if (empty($record->record_date)) {
            $record->record_date = now()->toDateString();
        }

        if (empty($record->company_id)) {
            $record->company_id = $record->employee?->company_id ?? auth()->user()?->company_id ?? auth()->user()?->employee?->company_id;
        }

        if (empty($record->created_by)) {
            $record->created_by = auth()->id();
        }
    }

    /**
     * Handle the PayrollRecord "saving" event.
     */
    public function saving(PayrollRecord $record): void
    {
        if (! empty($record->payroll_month)) {
            $record->payroll_month = Carbon::parse($record->payroll_month)->startOfMonth()->toDateString();
        }

        $record->basic_salary = (float) ($record->basic_salary ?? 0);
        $record->overtime_amount = (float) ($record->overtime_amount ?? 0);
        $record->rewards_amount = (float) ($record->rewards_amount ?? 0);
        $record->advance_deduction = (float) ($record->advance_deduction ?? 0);
        $record->absence_deduction = (float) ($record->absence_deduction ?? 0);
        $record->paid_amount = (float) ($record->paid_amount ?? 0);

        PayrollCalculationService::syncTotalsForRecord($record);
    }

    /**
     * Handle the PayrollRecord "deleting" event.
     */
    public function deleting(PayrollRecord $record): void
    {
        $record->isDeletingRecord = true;
        PayrollCalculationService::reverseDisbursementsForRecord($record, 'إلغاء وحذف مسير');
        $record->payments()->delete();
    }
}
