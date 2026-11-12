<?php

namespace App\Services;

use App\Enums\FinancialDisbursementStatus;
use App\Enums\FinancialDisbursementType;
use App\Enums\FinancialHandoverStatus;
use App\Enums\FinancialReceiptStatus;
use App\Models\CashHandoverItem;
use App\Models\FinancialDisbursement;
use App\Models\FinancialReceipt;
use App\Models\ServiceTransaction;
use Illuminate\Support\Collection;

/**
 * Single source of truth for the financial numbers of one service
 * transaction, read only from receipts, handover items, disbursements and
 * their linked treasury movements.
 */
class FinancialSummaryService
{
    public const SOURCE_TYPE = 'service_transaction';

    /**
     * @return array<string, float|int|bool|null>
     */
    public static function for(
        ServiceTransaction $transaction,
        ?Collection $receipts = null,
        ?Collection $disbursements = null,
        ?float $confirmedHandover = null,
    ): array {
        $sourceId = (int) $transaction->id;
        $snapshot = ServiceFinancialWorkflowService::snapshot($transaction);

        $receipts ??= FinancialReceipt::forSource(self::SOURCE_TYPE, $sourceId)->get();

        $confirmedReceipts = $receipts->filter(
            fn (FinancialReceipt $receipt) => $receipt->status === FinancialReceiptStatus::CONFIRMED
        );

        $pendingReceipts = $receipts->filter(
            fn (FinancialReceipt $receipt) => $receipt->status === FinancialReceiptStatus::PENDING_CONFIRMATION
        );

        $received = (float) $confirmedReceipts->sum(fn (FinancialReceipt $receipt) => (float) $receipt->amount);
        $cashReceived = (float) $confirmedReceipts
            ->filter(fn (FinancialReceipt $receipt) => $receipt->isCash())
            ->sum(fn (FinancialReceipt $receipt) => (float) $receipt->amount);
        $nonCashReceived = max(0.0, $received - $cashReceived);

        $receiptIds = $confirmedReceipts->pluck('id')->all();

        $treasuryReceived = $confirmedHandover ?? ($receiptIds === [] ? 0.0 : (float) CashHandoverItem::query()
            ->whereIn('financial_receipt_id', $receiptIds)
            ->whereHas('handover', fn ($query) => $query->where('status', FinancialHandoverStatus::CONFIRMED->value))
            ->sum('confirmed_amount'));

        $treasuryReceived += (float) $confirmedReceipts
            ->filter(fn (FinancialReceipt $receipt) => $receipt->isAllocatedTreasuryCash())
            ->sum('amount');

        $custody = max(0.0, $cashReceived - $treasuryReceived);

        $disbursements ??= FinancialDisbursement::forSource(self::SOURCE_TYPE, $sourceId)->with('payments')->get();
        $disbursements = $disbursements->reject(fn (FinancialDisbursement $disbursement) => $disbursement->isReversed());

        $directPaid = (float) $disbursements
            ->filter(fn (FinancialDisbursement $disbursement) => $disbursement->disbursement_type === FinancialDisbursementType::TREASURY_DIRECT)
            ->filter(fn (FinancialDisbursement $disbursement) => ! empty($disbursement->treasury_transaction_id))
            ->sum(fn (FinancialDisbursement $disbursement) => (float) $disbursement->amount);

        $personalSettled = (float) $disbursements
            ->filter(fn (FinancialDisbursement $disbursement) => $disbursement->disbursement_type === FinancialDisbursementType::EMPLOYEE_PERSONAL)
            ->filter(fn (FinancialDisbursement $disbursement) => in_array($disbursement->status, [
                FinancialDisbursementStatus::APPROVED,
                FinancialDisbursementStatus::PARTIALLY_REIMBURSED,
                FinancialDisbursementStatus::FULLY_REIMBURSED,
            ], true))
            ->sum(fn (FinancialDisbursement $disbursement) => (float) $disbursement->amount);

        $employeeDue = (float) $disbursements
            ->filter(fn (FinancialDisbursement $disbursement) => $disbursement->disbursement_type === FinancialDisbursementType::EMPLOYEE_PERSONAL)
            ->filter(fn (FinancialDisbursement $disbursement) => in_array($disbursement->status, [
                FinancialDisbursementStatus::APPROVED,
                FinancialDisbursementStatus::PARTIALLY_REIMBURSED,
            ], true))
            ->sum(fn (FinancialDisbursement $disbursement) => $disbursement->remainingDue());

        $awaitingApproval = (float) $disbursements
            ->filter(fn (FinancialDisbursement $disbursement) => $disbursement->status === FinancialDisbursementStatus::PENDING_APPROVAL)
            ->sum(fn (FinancialDisbursement $disbursement) => (float) $disbursement->amount);

        $reimbursedPaid = (float) $disbursements->sum(
            fn (FinancialDisbursement $disbursement) => $disbursement->reimbursedTotal()
        );

        $refunded = round((float) $confirmedReceipts->sum(fn (FinancialReceipt $receipt) => $receipt->refundedAmount()), 2);
        $netReceived = round($received - $refunded, 2);
        $treasuryPaid = round($directPaid + $reimbursedPaid + $refunded, 2);
        $netCash = round($treasuryReceived - $treasuryPaid, 2);

        $clientDue = array_key_exists('client_due_amount', $snapshot)
            ? (float) $snapshot['client_due_amount']
            : (float) $transaction->total_amount;

        $feeDefined = array_key_exists('expected_fee_amount', $snapshot)
            && $snapshot['expected_fee_amount'] !== null;
        $feeExpected = $feeDefined ? (float) $snapshot['expected_fee_amount'] : null;

        $clientRemaining = max(0.0, round($clientDue - $netReceived, 2));
        $surplus = max(0.0, round($netReceived - $clientDue, 2));
        $feePaid = round($directPaid + $personalSettled, 2);
        $feeRemaining = $feeExpected === null ? null : max(0.0, round($feeExpected - $feePaid, 2));

        $availableFunding = max(0.0, round($netCash - $surplus - $employeeDue, 2));
        $feeFundedRemaining = $feeRemaining === null ? 0.0 : min($feeRemaining, $availableFunding);

        return [
            'client_due' => round($clientDue, 2),
            'received' => round($received, 2),
            'applied_to_client' => round(min($netReceived, $clientDue), 2),
            'client_remaining' => $clientRemaining,
            'surplus' => $surplus,
            'surplus_refunded' => $refunded,
            'cash_received' => round($cashReceived, 2),
            'non_cash_received' => round($nonCashReceived, 2),
            'custody' => round($custody, 2),
            'treasury_received' => round($treasuryReceived, 2),
            'fee_defined' => $feeDefined,
            'fee_expected' => $feeExpected,
            'fee_paid' => $feePaid,
            'fee_remaining' => $feeRemaining,
            'fee_funded_remaining' => round($feeFundedRemaining, 2),
            'employee_due' => round($employeeDue, 2),
            'awaiting_approval' => round($awaitingApproval, 2),
            'treasury_paid' => $treasuryPaid,
            'reimbursed' => round($reimbursedPaid, 2),
            'net_cash' => $netCash,
            'pending_receipts' => round((float) $pendingReceipts->sum(fn (FinancialReceipt $receipt) => (float) $receipt->amount), 2),
            'pending_receipts_count' => $pendingReceipts->count(),
        ];
    }
}
