<?php

namespace App\Services;

use App\Enums\FinancialCashDirection;
use App\Enums\PaymentItemCategory;
use App\Enums\TreasuryTransactionType;
use App\Models\CashHandover;
use App\Models\FinancialDisbursement;
use App\Models\FinancialDisbursementPayment;
use App\Models\PaymentCollectionItem;
use App\Models\TreasuryTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Single writer for treasury movements created by the unified financial
 * workflow. It serializes the running balance per company before writing.
 */
class FinancialTreasuryService
{
    public static function balance(int $companyId): float
    {
        $last = TreasuryTransaction::where('company_id', $companyId)->orderByDesc('id')->first();

        return $last ? (float) $last->balance_after : 0.0;
    }

    public static function assertSufficientBalance(int $companyId, float $amount): void
    {
        if (static::balance($companyId) + 0.009 < $amount) {
            throw ValidationException::withMessages([
                'amount' => __('financial.errors.insufficient_treasury_balance'),
            ]);
        }
    }

    /**
     * Write one treasury movement with an explicit sequential balance.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function post(
        int $companyId,
        FinancialCashDirection $direction,
        float $amount,
        string $description,
        array $metadata = [],
        ?int $executedByEmployeeId = null,
        ?string $counterparty = null,
    ): TreasuryTransaction {
        $amount = round(abs($amount), 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => __('financial.errors.invalid_amount'),
            ]);
        }

        return DB::transaction(function () use ($companyId, $direction, $amount, $description, $metadata, $executedByEmployeeId, $counterparty) {
            // Serialize treasury writes for the same company (shared lock scope).
            DB::table('companies')->where('id', $companyId)->lockForUpdate()->first();

            $last = TreasuryTransaction::where('company_id', $companyId)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $previousBalance = $last ? (float) $last->balance_after : 0.0;
            $signed = $direction === FinancialCashDirection::INCOMING ? $amount : -$amount;

            $item = PaymentCollectionItem::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'main_item' => PaymentItemCategory::SERVICES,
                    'sub_item' => __('financial.payment_item_name'),
                ],
                ['service_price' => 0],
            );

            if ($direction === FinancialCashDirection::OUTGOING && $previousBalance + $signed < -0.009) {
                throw ValidationException::withMessages([
                    'amount' => __('financial.errors.insufficient_treasury_balance'),
                ]);
            }

            return TreasuryTransaction::create([
                'company_id' => $companyId,
                'transaction_date' => now()->toDateString(),
                'transaction_type' => $direction === FinancialCashDirection::INCOMING
                    ? TreasuryTransactionType::INCOMING
                    : TreasuryTransactionType::OUTGOING,
                'payment_collection_item_id' => $item->id,
                'recipient_or_payer' => $counterparty ?: __('financial.counterparty_default'),
                'amount' => $amount,
                'description' => $description,
                'executed_by' => $executedByEmployeeId,
                'metadata' => $metadata,
            ]);
        });
    }

    public static function directionOf(TreasuryTransaction $transaction): ?FinancialCashDirection
    {
        $type = $transaction->transaction_type instanceof TreasuryTransactionType
            ? $transaction->transaction_type
            : TreasuryTransactionType::tryFrom((string) $transaction->transaction_type);

        return match ($type) {
            TreasuryTransactionType::INCOMING => FinancialCashDirection::INCOMING,
            TreasuryTransactionType::OUTGOING => FinancialCashDirection::OUTGOING,
            default => null,
        };
    }

    public static function isAlreadyLinked(TreasuryTransaction $transaction): bool
    {
        return CashHandover::where('treasury_transaction_id', $transaction->id)->exists()
            || FinancialDisbursement::where('treasury_transaction_id', $transaction->id)->exists()
            || FinancialDisbursementPayment::where('treasury_transaction_id', $transaction->id)->exists();
    }

    /**
     * Validate that a treasury movement really belongs to the expected
     * company, direction and amount before linking it (no new movement).
     */
    public static function assertLinkable(
        TreasuryTransaction $transaction,
        int $companyId,
        FinancialCashDirection $direction,
        float $amount,
    ): void {
        if ((int) $transaction->company_id !== $companyId
            || static::directionOf($transaction) !== $direction
            || abs((float) $transaction->amount - round(abs($amount), 2)) > 0.009) {
            throw ValidationException::withMessages([
                'treasury_transaction_id' => __('financial.errors.posting_mismatch'),
            ]);
        }

        if (static::isAlreadyLinked($transaction)) {
            throw ValidationException::withMessages([
                'treasury_transaction_id' => __('financial.errors.posting_already_linked'),
            ]);
        }
    }
}
