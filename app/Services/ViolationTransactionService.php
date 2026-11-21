<?php

namespace App\Services;

use App\Enums\ViolationPaymentStatus;
use App\Enums\ViolationTransactionType;
use App\Models\Violation;
use App\Models\ViolationTransaction;
use Carbon\Carbon;

class ViolationTransactionService
{
    /**
     * Generate automatic voucher number for a transaction.
     */
    public static function generateVoucherNumber(?int $companyId): string
    {
        return CodeGeneratorService::generate(
            modelClass: ViolationTransaction::class,
            column: 'voucher_number',
            prefix: 'VT',
            companyId: $companyId,
            padLength: 4,
        );
    }

    /**
     * Calculate running balance after transaction for the given company.
     */
    public static function calculateBalanceAfter(ViolationTransaction $transaction): float
    {
        $lastTransaction = ViolationTransaction::where('company_id', $transaction->company_id)
            ->orderBy('id', 'desc')
            ->first();

        $previousBalance = $lastTransaction ? (float) $lastTransaction->balance_after : 0.0;
        $currentAmount   = (float) $transaction->amount;
        $transType       = $transaction->transaction_type instanceof ViolationTransactionType
            ? $transaction->transaction_type
            : ViolationTransactionType::tryFrom($transaction->transaction_type);

        if ($transType === ViolationTransactionType::INCOMING) {
            return $previousBalance + $currentAmount;
        }

        return $previousBalance - $currentAmount;
    }

    /**
     * Settle linked violation(s) for a given transaction.
     */
    public static function settleLinkedViolations(ViolationTransaction $transaction): void
    {
        $closureDate = $transaction->transaction_date
            ? Carbon::parse($transaction->transaction_date)->toDateString()
            : now()->toDateString();

        $selectedIds = $transaction->metadata['selected_violation_ids'] ?? [];

        if (! empty($selectedIds) && is_array($selectedIds)) {
            self::settleMultipleViolations($selectedIds, $closureDate);
        } elseif ($transaction->violation_id) {
            self::settleSingleViolation((int) $transaction->violation_id, (float) $transaction->amount, $closureDate);
        }
    }

    /**
     * Settle multiple violations by marking them paid in full.
     *
     * @param array<int> $violationIds
     */
    public static function settleMultipleViolations(array $violationIds, ?string $closureDate = null): void
    {
        if (empty($violationIds)) {
            return;
        }

        $date = $closureDate ?? now()->toDateString();

        $violations = Violation::whereIn('id', $violationIds)->get();
        foreach ($violations as $violation) {
            $violation->paid_amount = (float) $violation->violation_amount;
            $violation->remaining_amount = 0.0;
            $violation->payment_status = ViolationPaymentStatus::PAID;
            $violation->closure_date = $date;
            $violation->save();
        }
    }

    /**
     * Settle a single violation by applying payment amount.
     */
    public static function settleSingleViolation(int $violationId, float $amount, ?string $closureDate = null): void
    {
        $violation = Violation::find($violationId);
        if (! $violation) {
            return;
        }

        $date = $closureDate ?? now()->toDateString();
        $newPaid = (float) $violation->paid_amount + $amount;
        $violation->paid_amount = $newPaid;
        $violation->remaining_amount = max(0.0, (float) $violation->violation_amount - $newPaid);

        if ($violation->remaining_amount <= 0) {
            $violation->payment_status = ViolationPaymentStatus::PAID;
            $violation->closure_date = $date;
        } else {
            $violation->payment_status = ViolationPaymentStatus::PARTIAL;
        }

        $violation->save();
    }
}
