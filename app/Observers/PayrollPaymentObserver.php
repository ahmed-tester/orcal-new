<?php

namespace App\Observers;

use App\Models\PayrollPayment;
use App\Services\CodeGeneratorService;
use App\Services\PayrollCalculationService;

class PayrollPaymentObserver
{
    /**
     * Handle the PayrollPayment "creating" event.
     */
    public function creating(PayrollPayment $payment): void
    {
        if (empty($payment->payment_number)) {
            $payment->payment_number = CodeGeneratorService::generate(
                modelClass: PayrollPayment::class,
                column: 'payment_number',
                prefix: 'PAY',
                companyId: $payment->company_id,
                padLength: 4,
                datePart: 'Ym',
            );
        }

        if (empty($payment->payment_date)) {
            $payment->payment_date = now()->toDateString();
        }

        if (empty($payment->executed_by)) {
            $payment->executed_by = auth()->user()?->employee?->id;
        }
    }

    /**
     * Handle the PayrollPayment "saved" event.
     */
    public function saved(PayrollPayment $payment): void
    {
        $this->syncRecordPaidAmount($payment);
    }

    /**
     * Handle the PayrollPayment "deleting" event.
     */
    public function deleting(PayrollPayment $payment): void
    {
        if (! ($payment->payrollRecord?->isDeletingRecord ?? false)) {
            PayrollCalculationService::reverseDisbursementPayment($payment, 'إلغاء وحذف دفعة مسير');
        }
    }

    /**
     * Handle the PayrollPayment "deleted" event.
     */
    public function deleted(PayrollPayment $payment): void
    {
        $this->syncRecordPaidAmount($payment);
    }

    /**
     * Synchronize total paid amount on the parent PayrollRecord.
     */
    protected function syncRecordPaidAmount(PayrollPayment $payment): void
    {
        $record = $payment->payrollRecord;
        if ($record) {
            $totalPaid = (float) $record->payments()->sum('amount');
            $record->paid_amount = $totalPaid;
            $record->save();
        }
    }
}
