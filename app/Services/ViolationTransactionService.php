<?php

namespace App\Services;

use App\Enums\ViolationPaymentStatus;
use App\Enums\ViolationTransactionType;
use App\Models\Violation;
use App\Models\ViolationTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
     * Validate outgoing multiple-violation transaction amount against locked DB remaining amounts.
     *
     * @throws ValidationException
     */
    public static function validateMultipleSettlementAmount(ViolationTransaction $transaction, bool $isUpdate = false): void
    {
        if ($isUpdate && ! self::hasFinancialChanges($transaction)) {
            return;
        }

        $transType = self::resolveTransactionType($transaction->transaction_type);
        if ($transType !== ViolationTransactionType::OUTGOING) {
            return;
        }

        $selectedIds = array_values(array_unique(array_filter(array_map(
            'intval',
            $transaction->metadata['selected_violation_ids'] ?? []
        ))));

        if (empty($selectedIds)) {
            return;
        }

        DB::transaction(function () use ($transaction, $selectedIds, $isUpdate) {
            $violations = Violation::whereIn('id', $selectedIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $expectedTotal = 0.0;
            $oldSettled = [];

            if ($isUpdate) {
                $oldTransType = self::resolveTransactionType($transaction->getOriginal('transaction_type'));
                if ($oldTransType === ViolationTransactionType::OUTGOING) {
                    $oldSettled = self::resolveOldSettledAmounts($transaction, $violations, false, true);
                }
            }

            foreach ($selectedIds as $id) {
                $violation = $violations->get($id);
                if (! $violation) {
                    continue;
                }
                $availableRemaining = (float) $violation->remaining_amount + (float) ($oldSettled[$id] ?? 0.0);
                $expectedTotal += min((float) $violation->violation_amount, $availableRemaining);
            }

            $expectedTotal = round($expectedTotal, 2);
            $enteredAmount = round((float) $transaction->amount, 2);

            if ($expectedTotal <= 0 || abs($enteredAmount - $expectedTotal) > 0.01) {
                throw ValidationException::withMessages([
                    'amount' => __('violations.transactions.errors.multiple_amount_mismatch', [
                        'expected' => number_format($expectedTotal, 2),
                    ]),
                ]);
            }
        });
    }

    /**
     * Settle linked violation(s) for a newly created outgoing transaction.
     */
    public static function settleLinkedViolations(ViolationTransaction $transaction): void
    {
        $transType = self::resolveTransactionType($transaction->transaction_type);

        if ($transType !== ViolationTransactionType::OUTGOING) {
            return;
        }

        $closureDate = $transaction->transaction_date
            ? Carbon::parse($transaction->transaction_date)->toDateString()
            : now()->toDateString();

        $selectedIds = array_values(array_unique(array_filter(array_map(
            'intval',
            $transaction->metadata['selected_violation_ids'] ?? []
        ))));

        DB::transaction(function () use ($transaction, $selectedIds, $closureDate) {
            if (! empty($selectedIds)) {
                $settledAmounts = self::settleMultipleViolations($selectedIds, $closureDate, (float) $transaction->amount);
                $meta = $transaction->metadata ?? [];
                $meta['settled_amounts'] = $settledAmounts;
                $transaction->metadata = $meta;
                $transaction->saveQuietly();
            } elseif ($transaction->violation_id) {
                self::settleSingleViolation((int) $transaction->violation_id, (float) $transaction->amount, $closureDate);
            }
        });
    }

    /**
     * Reverse previous settlement and apply updated transaction financial effects.
     */
    public static function syncUpdatedTransaction(ViolationTransaction $transaction): void
    {
        $oldCompanyId = $transaction->getOriginal('company_id');
        $newCompanyId = $transaction->company_id;
        $financialChanged = self::hasFinancialChanges($transaction);

        DB::transaction(function () use ($transaction, $oldCompanyId, $newCompanyId, $financialChanged) {
            if ($financialChanged) {
                self::reverseOriginalSettlement($transaction);

                $transType = self::resolveTransactionType($transaction->transaction_type);
                if ($transType === ViolationTransactionType::OUTGOING) {
                    $closureDate = $transaction->transaction_date
                        ? Carbon::parse($transaction->transaction_date)->toDateString()
                        : now()->toDateString();

                    $selectedIds = array_values(array_unique(array_filter(array_map(
                        'intval',
                        $transaction->metadata['selected_violation_ids'] ?? []
                    ))));

                    if (! empty($selectedIds)) {
                        $settledAmounts = self::settleMultipleViolations($selectedIds, $closureDate, (float) $transaction->amount);
                        $meta = $transaction->metadata ?? [];
                        $meta['settled_amounts'] = $settledAmounts;
                        $transaction->metadata = $meta;
                        $transaction->saveQuietly();
                    } else {
                        $meta = $transaction->metadata ?? [];
                        if (array_key_exists('settled_amounts', $meta)) {
                            unset($meta['settled_amounts']);
                            $transaction->metadata = $meta;
                            $transaction->saveQuietly();
                        }

                        if ($transaction->violation_id) {
                            self::settleSingleViolation((int) $transaction->violation_id, (float) $transaction->amount, $closureDate);
                        }
                    }
                } else {
                    $meta = $transaction->metadata ?? [];
                    if (array_key_exists('settled_amounts', $meta)) {
                        unset($meta['settled_amounts']);
                        $transaction->metadata = $meta;
                        $transaction->saveQuietly();
                    }
                }
            }

            if ($oldCompanyId && (int) $oldCompanyId !== (int) $newCompanyId) {
                self::recalculateCompanyBalances((int) $oldCompanyId);
            }
            if ($financialChanged || ((int) $oldCompanyId !== (int) $newCompanyId)) {
                self::recalculateCompanyBalances($newCompanyId ? (int) $newCompanyId : null);
            }
        });
    }

    /**
     * Determine whether financial or linkage attributes changed on update.
     */
    protected static function hasFinancialChanges(ViolationTransaction $transaction): bool
    {
        if ($transaction->isDirty(['amount', 'transaction_type', 'company_id', 'violation_id'])) {
            return true;
        }

        $oldMetaRaw = $transaction->getOriginal('metadata');
        $oldMeta = is_string($oldMetaRaw) ? (json_decode($oldMetaRaw, true) ?? []) : ($oldMetaRaw ?? []);
        $newMeta = $transaction->metadata ?? [];

        $oldSelected = array_values(array_map('intval', $oldMeta['selected_violation_ids'] ?? []));
        $newSelected = array_values(array_map('intval', $newMeta['selected_violation_ids'] ?? []));
        sort($oldSelected);
        sort($newSelected);

        return $oldSelected !== $newSelected;
    }

    /**
     * Resolve per-violation amounts settled by a transaction.
     * Uses exact `settled_amounts` when recorded; for legacy multiple vouchers without it,
     * only resolves when the voucher amount matches the sum of full violation amounts,
     * otherwise blocks ambiguous financial reversal/editing.
     *
     * @param Collection<int, Violation> $violationsById
     * @return array<int, float>
     * @throws ValidationException
     */
    public static function resolveOldSettledAmounts(
        ViolationTransaction $transaction,
        Collection $violationsById,
        bool $useCurrentAttributes = false,
        bool $throwOnAmbiguousLegacy = false,
    ): array {
        $metaRaw = $useCurrentAttributes ? $transaction->metadata : $transaction->getOriginal('metadata');
        $meta = is_string($metaRaw) ? (json_decode($metaRaw, true) ?? []) : ($metaRaw ?? []);

        $settled = $meta['settled_amounts'] ?? [];
        if (! empty($settled) && is_array($settled)) {
            $result = [];
            foreach ($settled as $id => $amt) {
                $result[(int) $id] = (float) $amt;
            }
            return $result;
        }

        $selectedIds = array_values(array_unique(array_filter(array_map(
            'intval',
            $meta['selected_violation_ids'] ?? []
        ))));

        $voucherAmount = (float) ($useCurrentAttributes ? $transaction->amount : $transaction->getOriginal('amount'));

        if (! empty($selectedIds)) {
            $missingIds = array_diff($selectedIds, $violationsById->keys()->all());
            $allViolations = empty($missingIds)
                ? $violationsById
                : Violation::whereIn('id', $selectedIds)->get()->keyBy('id');

            $fullSum = round((float) $allViolations->only($selectedIds)->sum('violation_amount'), 2);
            if ($allViolations->only($selectedIds)->count() === count($selectedIds) && abs($fullSum - round($voucherAmount, 2)) <= 0.01) {
                $result = [];
                foreach ($selectedIds as $id) {
                    $result[$id] = (float) $allViolations->get($id)->violation_amount;
                }
                return $result;
            }

            if ($throwOnAmbiguousLegacy) {
                throw ValidationException::withMessages([
                    'amount' => __('violations.transactions.errors.legacy_multiple_edit_blocked'),
                ]);
            }

            return [];
        }

        $violationId = $useCurrentAttributes ? $transaction->violation_id : $transaction->getOriginal('violation_id');
        if ($violationId) {
            return [(int) $violationId => $voucherAmount];
        }

        return [];
    }

    /**
     * Reverse the violation settlement applied by the original state of a transaction.
     */
    protected static function reverseOriginalSettlement(ViolationTransaction $transaction): void
    {
        $oldType = self::resolveTransactionType($transaction->getOriginal('transaction_type'));
        if ($oldType !== ViolationTransactionType::OUTGOING) {
            return;
        }

        $oldMetaRaw = $transaction->getOriginal('metadata');
        $oldMeta = is_string($oldMetaRaw) ? (json_decode($oldMetaRaw, true) ?? []) : ($oldMetaRaw ?? []);

        $ids = [];
        if (! empty($oldMeta['settled_amounts']) && is_array($oldMeta['settled_amounts'])) {
            $ids = array_map('intval', array_keys($oldMeta['settled_amounts']));
        } elseif (! empty($oldMeta['selected_violation_ids']) && is_array($oldMeta['selected_violation_ids'])) {
            $ids = array_map('intval', $oldMeta['selected_violation_ids']);
        } elseif ($transaction->getOriginal('violation_id')) {
            $ids = [(int) $transaction->getOriginal('violation_id')];
        }

        if (empty($ids)) {
            return;
        }

        $violations = Violation::whereIn('id', array_values(array_unique($ids)))
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $oldSettled = self::resolveOldSettledAmounts($transaction, $violations, false, true);

        foreach ($oldSettled as $id => $delta) {
            if ($violation = $violations->get((int) $id)) {
                self::revertViolationPayment($violation, (float) $delta);
            }
        }
    }

    /**
     * Revert a payment delta on a locked violation record.
     */
    protected static function revertViolationPayment(Violation $violation, float $delta): void
    {
        $newPaid = max(0.0, round((float) $violation->paid_amount - $delta, 2));
        $newRemaining = max(0.0, round((float) $violation->violation_amount - $newPaid, 2));

        $violation->paid_amount = $newPaid;
        $violation->remaining_amount = $newRemaining;

        if ($newRemaining <= 0) {
            $violation->payment_status = ViolationPaymentStatus::PAID;
        } elseif ($newPaid > 0) {
            $violation->payment_status = ViolationPaymentStatus::PARTIAL;
            $violation->closure_date = null;
        } else {
            $violation->payment_status = ViolationPaymentStatus::PENDING;
            $violation->closure_date = null;
        }

        $violation->save();
    }

    /**
     * Recalculate running balance_after for all transactions of a company.
     */
    public static function recalculateCompanyBalances(?int $companyId): void
    {
        if (! $companyId) {
            return;
        }

        $transactions = ViolationTransaction::where('company_id', $companyId)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $running = 0.0;
        foreach ($transactions as $item) {
            $type = self::resolveTransactionType($item->transaction_type);
            $amount = (float) $item->amount;
            $running = $type === ViolationTransactionType::INCOMING
                ? $running + $amount
                : $running - $amount;

            if (abs((float) $item->balance_after - $running) > 0.001) {
                $item->balance_after = $running;
                $item->saveQuietly();
            }
        }
    }

    /**
     * Settle multiple violations by marking them paid in full after verifying locked total.
     *
     * @param array<int> $violationIds
     * @return array<int, float> Map of violation_id => amount settled
     * @throws ValidationException
     */
    public static function settleMultipleViolations(array $violationIds, ?string $closureDate = null, ?float $expectedAmount = null): array
    {
        if (empty($violationIds)) {
            return [];
        }

        $date = $closureDate ?? now()->toDateString();
        $ids = array_values(array_unique(array_map('intval', $violationIds)));

        $violations = Violation::whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $actualRemainingTotal = round((float) $violations->sum('remaining_amount'), 2);

        if ($expectedAmount !== null && ($actualRemainingTotal <= 0 || abs(round($expectedAmount, 2) - $actualRemainingTotal) > 0.01)) {
            throw ValidationException::withMessages([
                'amount' => __('violations.transactions.errors.multiple_amount_mismatch', [
                    'expected' => number_format($actualRemainingTotal, 2),
                ]),
            ]);
        }

        $settledAmounts = [];
        foreach ($violations as $violation) {
            $settledDelta = (float) $violation->remaining_amount;
            $settledAmounts[$violation->id] = $settledDelta;

            $violation->paid_amount = (float) $violation->violation_amount;
            $violation->remaining_amount = 0.0;
            $violation->payment_status = ViolationPaymentStatus::PAID;
            $violation->closure_date = $date;
            $violation->save();
        }

        return $settledAmounts;
    }

    /**
     * Settle a single violation by applying payment amount with row lock.
     */
    public static function settleSingleViolation(int $violationId, float $amount, ?string $closureDate = null): void
    {
        $violation = Violation::whereKey($violationId)->lockForUpdate()->first();
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

    private static function resolveTransactionType(mixed $type): ?ViolationTransactionType
    {
        return $type instanceof ViolationTransactionType
            ? $type
            : ViolationTransactionType::tryFrom((string) $type);
    }
}
