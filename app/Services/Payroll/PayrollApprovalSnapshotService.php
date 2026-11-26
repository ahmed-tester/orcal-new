<?php

namespace App\Services\Payroll;

use App\Enums\FinancialPaymentMethod;
use App\Models\Payroll\FinancialApprovalRequestItem;
use App\Models\Payroll\PayrollRecord;
use Illuminate\Validation\ValidationException;

class PayrollApprovalSnapshotService
{
    /**
     * Build a canonical financial snapshot from the current source PayrollRecord.
     *
     * @return array<string, mixed>
     */
    public static function buildSnapshot(
        PayrollRecord $record,
        float $requestedAmount,
        FinancialPaymentMethod $method,
        string $monthStr,
    ): array {
        $branchId = $record->metadata['branch_id_snapshot'] ?? $record->employee?->branch_id;
        $available = $record->isManaged()
            ? (float) PayrollRecordFinancialService::payrollSummary($record)['available_for_disbursement']
            : (float) $record->remaining_amount;

        $reservedDeductions = collect($record->metadata['linked_collection_deductions'] ?? [])
            ->where('status', 'reserved')
            ->keys()
            ->map(fn ($k) => (string) $k)
            ->sort()
            ->values()
            ->all();

        return [
            'payroll_record_id' => (int) $record->id,
            'employee_id' => (int) $record->employee_id,
            'company_id' => (int) $record->company_id,
            'branch_id' => $branchId ? (int) $branchId : null,
            'payroll_month' => substr((string) $monthStr, 0, 7),
            'operation_type' => (string) $record->operation_type,
            'basic_salary' => round((float) $record->basic_salary, 2),
            'additional_amount' => round((float) $record->additional_amount, 2),
            'deduction_amount' => round((float) $record->deduction_amount, 2),
            'advance_deduction' => round((float) ($record->advance_deduction ?? 0), 2),
            'absence_deduction' => round((float) ($record->absence_deduction ?? 0), 2),
            'net_amount' => round((float) $record->net_amount, 2),
            'previously_paid_amount' => round((float) ($record->paid_amount ?? 0), 2),
            'remaining_amount' => round((float) $record->remaining_amount, 2),
            'available_for_disbursement' => round($available, 2),
            'reserved_deduction_keys' => $reservedDeductions,
            'requested_amount' => round($requestedAmount, 2),
            'payment_method' => $method->value,
        ];
    }

    /**
     * Compute a deterministic SHA-256 fingerprint for a canonical snapshot array.
     *
     * @param  array<string, mixed>  $snapshot
     */
    public static function computeFingerprint(array $snapshot): string
    {
        return hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR));
    }

    /**
     * Verify that the live source PayrollRecord still matches the approved item's snapshot.
     */
    public static function verifyItemMatchesSourceRecord(FinancialApprovalRequestItem $item, PayrollRecord $currentRecord): void
    {
        if ((int) $item->payroll_record_id !== (int) $currentRecord->id
            || (int) $item->company_id !== (int) $currentRecord->company_id
            || (int) $item->employee_id !== (int) $currentRecord->employee_id
            || ! $currentRecord->isPayroll()) {
            throw ValidationException::withMessages([
                'items' => __('financial.errors.approval_item_fingerprint_mismatch'),
            ]);
        }

        $method = FinancialPaymentMethod::tryFrom((string) $item->payment_method);
        if (! $method instanceof FinancialPaymentMethod) {
            throw ValidationException::withMessages([
                'payment_method' => __('financial.errors.invalid_payment_method'),
            ]);
        }

        $currentMonth = substr((string) $currentRecord->payroll_month, 0, 7);
        if ($currentMonth !== substr((string) $item->payroll_month, 0, 7)) {
            throw ValidationException::withMessages([
                'items' => __('financial.errors.approval_item_fingerprint_mismatch'),
            ]);
        }

        $liveSnapshot = static::buildSnapshot(
            record: $currentRecord,
            requestedAmount: (float) $item->requested_amount,
            method: $method,
            monthStr: (string) $item->payroll_month,
        );

        $liveFingerprint = static::computeFingerprint($liveSnapshot);

        if ($item->data_fingerprint !== $liveFingerprint) {
            throw ValidationException::withMessages([
                'items' => __('financial.errors.approval_item_fingerprint_mismatch'),
            ]);
        }
    }
}
