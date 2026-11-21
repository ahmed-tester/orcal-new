<?php

namespace App\Services;

use App\Enums\FinancialCashDirection;
use App\Models\FinancialReceipt;
use App\Models\ServiceTransaction;
use App\Models\TreasuryTransaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FinancialSurplusRefundService
{
    public const TREASURY_SOURCE = 'service_cash_surplus_refund';

    /**
     * Return the next eligible receipt and its outstanding cash surplus.
     *
     * @return array{receipt: FinancialReceipt, amount: float}|null
     */
    public static function quote(ServiceTransaction $transaction, ?Collection $receipts = null): ?array
    {
        $receipts ??= FinancialReceipt::forSource(FinancialReceipt::SOURCE_SERVICE_TRANSACTION, $transaction->id)
            ->confirmed()->orderBy('id')->get();

        $snapshot = ServiceFinancialWorkflowService::snapshot($transaction);
        $due = (float) ($snapshot['client_due_amount'] ?? $transaction->total_amount);

        foreach ($receipts as $receipt) {
            $received = (float) $receipt->amount;
            $applied = min($due, $received);
            $due = round($due - $applied, 2);
            $surplus = round($received - $applied - $receipt->refundedAmount(), 2);

            if ($surplus > 0 && $receipt->isCash() && ! $receipt->isAllocatedTreasuryCash()
                && round($receipt->handedOverAmount(), 2) >= $received) {
                return ['receipt' => $receipt, 'amount' => $surplus];
            }
        }

        return null;
    }

    public static function confirm(
        ServiceTransaction $transaction,
        int $receiptId,
        float $expectedAmount,
        string $requestKey,
        ?User $user = null,
    ): TreasuryTransaction {
        $user = ServiceFinancialWorkflowService::authorize($transaction, 'confirm_cash_handover', $user);
        Gate::forUser($user)->authorize('create', TreasuryTransaction::class);
        ServiceFinancialWorkflowService::assertManaged($transaction);

        if (! Str::isUuid($requestKey)) {
            throw ValidationException::withMessages([
                'refund_amount' => __('financial.errors.request_key_required'),
            ]);
        }

        return DB::transaction(function () use ($transaction, $receiptId, $expectedAmount, $requestKey, $user) {
            $locked = ServiceTransaction::whereKey($transaction->id)->lockForUpdate()->firstOrFail();
            $receipts = FinancialReceipt::forSource(FinancialReceipt::SOURCE_SERVICE_TRANSACTION, $locked->id)
                ->confirmed()->orderBy('id')->lockForUpdate()->get();

            ServiceFinancialWorkflowService::authorize($locked, 'confirm_cash_handover', $user);
            ServiceFinancialWorkflowService::assertManaged($locked);

            foreach ($receipts as $receipt) {
                $refund = $receipt->metadata['surplus_refunds'][$requestKey] ?? null;

                if ($refund) {
                    if ($receipt->id !== $receiptId || (float) $refund['amount'] !== round($expectedAmount, 2)) {
                        throw ValidationException::withMessages([
                            'refund_amount' => __('financial.errors.request_key_reused'),
                        ]);
                    }

                    return TreasuryTransaction::findOrFail($refund['treasury_transaction_id']);
                }
            }

            $quote = static::quote($locked, $receipts);
            if ($quote === null || $quote['receipt']->id !== $receiptId || $quote['amount'] !== round($expectedAmount, 2)) {
                throw ValidationException::withMessages([
                    'refund_amount' => __('financial.errors.surplus_refund_changed'),
                ]);
            }

            return static::post($locked, $quote['receipt'], $quote['amount'], $requestKey, $user);
        });
    }

    private static function post(
        ServiceTransaction $transaction,
        FinancialReceipt $receipt,
        float $amount,
        string $requestKey,
        User $user,
    ): TreasuryTransaction {
        $movement = FinancialTreasuryService::post(
            companyId: (int) $transaction->company_id,
            direction: FinancialCashDirection::OUTGOING,
            amount: $amount,
            description: __('financial.descriptions.surplus_refund', [
                'number' => $transaction->transaction_number,
                'receipt' => $receipt->receipt_number,
            ]),
            metadata: [
                'source' => self::TREASURY_SOURCE,
                'service_transaction_id' => $transaction->id,
                'financial_receipt_id' => $receipt->id,
                'request_key' => $requestKey,
                'refunded_by' => $user->id,
            ],
            executedByEmployeeId: $user->employee?->id,
            counterparty: $receipt->payer_name ?: $transaction->display_beneficiary,
        );

        $metadata = $receipt->metadata ?? [];
        $metadata['surplus_refunds'][$requestKey] = [
            'amount' => $amount,
            'treasury_transaction_id' => $movement->id,
        ];
        $receipt->update(['metadata' => $metadata]);

        ServiceFinancialWorkflowService::sync($transaction);
        $transaction = OperationsReconciliationService::reconcile($transaction);

        OperationsAuditService::record(
            companyId: (int) $transaction->company_id,
            transactionId: $transaction->id,
            actionType: 'financial_surplus_refunded',
            payload: [
                'receipt_number' => $receipt->receipt_number,
                'amount' => $amount,
                'treasury_transaction_id' => $movement->id,
            ],
            userId: $user->id,
        );

        return $movement;
    }
}
