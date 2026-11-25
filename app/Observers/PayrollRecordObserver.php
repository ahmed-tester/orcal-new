<?php

namespace App\Observers;

use App\Enums\PaymentStatus;
use App\Models\PayrollRecord;
use App\Services\CodeGeneratorService;
use App\Services\Payroll\PayrollCalculationService;
use App\Services\Payroll\SaudiCompensationGrantService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

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

        if ($record->exists && $record->hasFinancialImpact()) {
            foreach ([
                'basic_salary',
                'overtime_amount',
                'rewards_amount',
                'additional_amount',
                'advance_deduction',
                'absence_deduction',
                'deduction_amount',
                'net_amount',
            ] as $protectedColumn) {
                if ($record->isDirty($protectedColumn)) {
                    $record->{$protectedColumn} = $record->getOriginal($protectedColumn);
                }
            }

            if (! $record->isManaged()) {
                $net = (float) ($record->net_amount ?? 0);
                $paid = (float) ($record->paid_amount ?? 0);
                $record->remaining_amount = round(max(0.0, $net - $paid), 2);
                $record->payment_status = ($paid >= $net && $net > 0)
                    ? PaymentStatus::PAID
                    : ($paid > 0 ? PaymentStatus::PARTIALLY_PAID : PaymentStatus::UNPAID);
            }

            return;
        }

        if ($record->skipObserverTotalsSync) {
            if ($record->isManaged()) {
                $meta = $record->metadata ?? [];
                $meta['net_amount_snapshot'] = round((float) $record->net_amount, 2);
                $record->metadata = $meta;
            }

            return;
        }

        if (! $record->isManaged()) {
            PayrollCalculationService::syncTotalsForRecord($record);
        } else {
            PayrollCalculationService::syncTotalsForRecord($record);
            $meta = $record->metadata ?? [];
            $meta['net_amount_snapshot'] = round((float) $record->net_amount, 2);
            $record->metadata = $meta;
        }
    }

    /**
     * Handle the PayrollRecord "deleting" event.
     */
    public function deleting(PayrollRecord $record): void
    {
        if ($record->isManaged() && $record->hasFinancialImpact()) {
            throw ValidationException::withMessages([
                'payroll_record' => __('financial.errors.cannot_delete_with_financial_records'),
            ]);
        }

        $record->isDeletingRecord = true;
        SaudiCompensationGrantService::reverseAllocationsForRecord($record, 'إلغاء وحذف مسير');
        PayrollCalculationService::reverseDisbursementsForRecord($record, 'إلغاء وحذف مسير');
        $record->payments()->delete();
    }
}
