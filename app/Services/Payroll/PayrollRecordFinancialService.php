<?php

namespace App\Services\Payroll;

use App\Enums\PaymentStatus;
use App\Models\FinancialReceipt;
use App\Models\Payroll\PayrollPayment;
use App\Models\Payroll\PayrollRecord;
use App\Models\User;
use App\Services\FinancialSummaryService;
use App\Services\OperationsAuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollRecordFinancialService
{
    public static function activate(PayrollRecord $record, ?User $actor = null): PayrollRecord
    {
        $meta = $record->metadata ?? [];
        $meta[PayrollRecord::FLOW_KEY] = PayrollRecord::WORKFLOW_VERSION;
        $meta['financial_flow'] = PayrollRecord::FLOW_MANAGED;
        $meta['managed_activated_by'] = $meta['managed_activated_by'] ?? $actor?->id ?? auth()->id();
        $meta['managed_activated_at'] = $meta['managed_activated_at'] ?? now()->toDateTimeString();
        $meta['branch_id_snapshot'] = $meta['branch_id_snapshot'] ?? $record->employee?->branch_id;
        $meta['net_amount_snapshot'] = $meta['net_amount_snapshot'] ?? round((float) $record->net_amount, 2);

        $net = round(max(0.0, (float) $record->net_amount), 2);

        $record->metadata = $meta;
        $record->paid_amount = 0.0;
        $record->remaining_amount = $net;
        $record->payment_status = $net > 0 ? PaymentStatus::UNPAID : PaymentStatus::PAID;
        $record->saveQuietly();

        return $record;
    }

    /**
     * Activate a new or strictly empty legacy PayrollRecord on its first explicit financial operation.
     */
    public static function ensureManaged(PayrollRecord $record, ?User $actor = null): PayrollRecord
    {
        if ($record->isManaged()) {
            return $record;
        }

        return DB::transaction(function () use ($record, $actor): PayrollRecord {
            $locked = PayrollRecord::whereKey($record->id)->lockForUpdate()->firstOrFail();

            if ($locked->isManaged()) {
                return $locked;
            }

            $meta = $locked->metadata ?? [];
            $hasPayments = PayrollPayment::where('payroll_record_id', $locked->id)->exists();
            $hasReceipts = FinancialReceipt::forSource(FinancialReceipt::SOURCE_PAYROLL_COLLECTION, (int) $locked->id)->exists();

            if ((float) $locked->paid_amount > 0
                || $hasPayments
                || $hasReceipts
                || $locked->payment_status === PaymentStatus::PAID
                || $locked->payment_status === PaymentStatus::PARTIALLY_PAID
                || ! empty($meta['reversed_at'])
                || ! empty($meta['salary_deduction_allocations'])
                || ! empty($meta['linked_collection_deductions'])
                || ! empty($meta['pending_bank_transfers'])) {
                throw ValidationException::withMessages([
                    'payroll_record' => __('financial.errors.legacy_record_not_eligible'),
                ]);
            }

            static::activate($locked, $actor);

            OperationsAuditService::recordFor(
                model: $locked,
                actionType: 'payroll_record_managed_activated',
                payload: [
                    'record_number' => $locked->record_number,
                    'operation_type' => $locked->operation_type,
                    'net_amount' => (float) $locked->net_amount,
                ],
                userId: $actor?->id ?? auth()->id(),
                companyId: (int) $locked->company_id,
            );

            return $locked->refresh();
        });
    }

    /**
     * @return array<string, float|int>
     */
    public static function collectionSummary(PayrollRecord $record): array
    {
        $base = FinancialSummaryService::forReceipts(
            FinancialReceipt::SOURCE_PAYROLL_COLLECTION,
            (int) $record->id,
            (float) $record->net_amount,
        );

        $allocations = collect($record->metadata['salary_deduction_allocations'] ?? []);
        $deductionSettled = round((float) $allocations
            ->where('status', 'confirmed')
            ->sum('amount'), 2);
        $deductionReserved = round((float) $allocations
            ->where('status', 'reserved')
            ->sum('amount'), 2);

        $due = (float) $base['due'];
        $receiptApplied = (float) $base['net_received'];
        $pendingReceipts = (float) $base['pending_receipts'];
        $totalSettled = round(min($due, $receiptApplied + $deductionSettled), 2);
        $remaining = round(max(0.0, $due - $totalSettled), 2);
        $available = round(max(0.0, $due - $totalSettled - $deductionReserved - $pendingReceipts), 2);

        return array_merge($base, [
            'receipt_received' => $receiptApplied,
            'deduction_settled' => $deductionSettled,
            'deduction_reserved' => $deductionReserved,
            'total_settled' => $totalSettled,
            'remaining' => $remaining,
            'available_to_settle' => $available,
        ]);
    }

    public static function syncCollection(PayrollRecord $record): PayrollRecord
    {
        if (! $record->isManaged() || ! $record->isCollection()) {
            return $record;
        }

        $summary = static::collectionSummary($record);
        $meta = $record->metadata ?? [];

        if ((float) $summary['cash_received'] > 0) {
            $meta['custody_status'] = (float) $summary['custody'] > 0 ? 'on_employee' : 'in_treasury';
        } elseif ((float) $summary['received'] > 0) {
            $meta['custody_status'] = 'not_applicable';
        } else {
            $meta['custody_status'] = 'none';
        }

        $meta['financial_synced_at'] = now()->toDateTimeString();

        $settled = (float) $summary['total_settled'];
        $remaining = (float) $summary['remaining'];
        $due = (float) $summary['due'];

        $record->metadata = $meta;
        $record->paid_amount = $settled;
        $record->remaining_amount = $remaining;
        $record->payment_status = match (true) {
            $remaining <= 0 && ($settled > 0 || $due <= 0) => PaymentStatus::PAID,
            $settled > 0 => PaymentStatus::PARTIALLY_PAID,
            default => PaymentStatus::UNPAID,
        };
        $record->saveQuietly();

        return $record->refresh();
    }

    /**
     * @return array<string, float>
     */
    public static function payrollSummary(PayrollRecord $record): array
    {
        $grossNet = $record->hasFinancialImpact()
            ? round(max(0.0, (float) ($record->metadata['net_amount_snapshot'] ?? $record->net_amount)), 2)
            : round(max(0.0, (float) $record->net_amount), 2);

        $linkedDeductions = collect($record->metadata['linked_collection_deductions'] ?? []);
        $deductionConfirmed = round((float) $linkedDeductions->where('status', 'confirmed')->sum('amount'), 2);
        $deductionReserved = round((float) $linkedDeductions->where('status', 'reserved')->sum('amount'), 2);

        $effectiveNet = round(max(0.0, $grossNet - $deductionConfirmed), 2);

        $activePayments = $record->payments()
            ->get()
            ->filter(fn (PayrollPayment $p) => empty($p->metadata['reversed_at']));

        $paid = round((float) $activePayments->sum(fn (PayrollPayment $p) => (float) $p->amount), 2);
        $remaining = round(max(0.0, $effectiveNet - $paid), 2);
        $available = round(max(0.0, $remaining - $deductionReserved), 2);

        return [
            'gross_net' => $grossNet,
            'deduction_confirmed' => $deductionConfirmed,
            'deduction_reserved' => $deductionReserved,
            'effective_net' => $effectiveNet,
            'paid' => $paid,
            'remaining' => $remaining,
            'available_for_disbursement' => $available,
        ];
    }

    public static function syncPayroll(PayrollRecord $record): PayrollRecord
    {
        if (! $record->isManaged() || ! $record->isPayroll()) {
            return $record;
        }

        $summary = static::payrollSummary($record);
        $meta = $record->metadata ?? [];
        $meta['financial_synced_at'] = now()->toDateTimeString();

        $paid = (float) $summary['paid'];
        $remaining = (float) $summary['remaining'];
        $effectiveNet = (float) $summary['effective_net'];

        $record->metadata = $meta;
        $record->paid_amount = $paid;
        $record->remaining_amount = $remaining;
        $record->payment_status = match (true) {
            $remaining <= 0 && ($paid > 0 || $effectiveNet <= 0) => PaymentStatus::PAID,
            $paid > 0 => PaymentStatus::PARTIALLY_PAID,
            default => PaymentStatus::UNPAID,
        };
        $record->saveQuietly();

        return $record->refresh();
    }

    /**
     * Reserve a salary deduction allocation between a collection record and a payroll record.
     *
     * @return array<string, mixed>
     */
    public static function reserveCollectionDeduction(
        PayrollRecord $collection,
        PayrollRecord $payroll,
        float $amount,
        string $requestKey,
        ?User $actor = null,
    ): array {
        return PayrollDeductionSettlementService::reserve($collection, $payroll, $amount, $requestKey, $actor);
    }

    /**
     * @return array<string, mixed>
     */
    public static function confirmCollectionDeduction(
        PayrollRecord $collection,
        PayrollRecord $payroll,
        string $requestKey,
        ?User $actor = null,
        bool $allowZeroCashOnly = false,
    ): array {
        return PayrollDeductionSettlementService::confirm($collection, $payroll, $requestKey, $actor, $allowZeroCashOnly);
    }

    /**
     * @param  array<int, string>  $requestKeys
     * @return array<int, array<string, mixed>>
     */
    public static function confirmCollectionDeductionsBatch(
        PayrollRecord $payroll,
        array $requestKeys,
        ?User $actor = null,
        bool $allowZeroCashOnly = false,
    ): array {
        return PayrollDeductionSettlementService::confirmBatch($payroll, $requestKeys, $actor, $allowZeroCashOnly);
    }

    /**
     * @return array<string, mixed>
     */
    public static function reverseCollectionDeduction(
        PayrollRecord $collection,
        PayrollRecord $payroll,
        string $requestKey,
        string $reason,
        ?User $actor = null,
    ): array {
        return PayrollDeductionSettlementService::reverse($collection, $payroll, $requestKey, $reason, $actor);
    }
}
