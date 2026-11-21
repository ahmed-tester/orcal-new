<?php

namespace App\Services;

use App\Enums\FinancialDisbursementStatus;
use App\Enums\FinancialDisbursementType;
use App\Enums\FinancialHandoverStatus;
use App\Enums\FinancialReceiptStatus;
use App\Models\ServiceTransaction;
use Illuminate\Database\Eloquent\Builder;

class ServiceFinancialIndicatorsService
{
    public static function for(Builder $scopedTransactions): array
    {
        $rows = array_fill_keys(['company', 'service', 'collector', 'deliverer', 'employee'], []);
        $query = (clone $scopedTransactions)->reorder()->select([
            'service_transactions.id', 'company_id', 'service_id', 'scope_type', 'service_name', 'metadata', 'total_amount',
        ])->with([
            'company:id,trade_name',
            'service:id,name',
            'financialReceipts' => fn ($query) => $query
                ->select('id', 'source_id', 'status', 'payment_method', 'amount', 'metadata', 'collector_employee_id')
                ->where('status', FinancialReceiptStatus::CONFIRMED->value),
            'financialReceipts.collector:id,user_id',
            'financialReceipts.collector.user:id,name',
            'financialReceipts.handoverItems' => fn ($query) => $query
                ->select('id', 'financial_receipt_id', 'cash_handover_id', 'confirmed_amount')
                ->whereHas('handover', fn ($handover) => $handover->where('status', FinancialHandoverStatus::CONFIRMED->value)),
            'financialReceipts.handoverItems.handover:id,status,delivered_by_employee_id',
            'financialReceipts.handoverItems.handover.deliveredBy:id,user_id',
            'financialReceipts.handoverItems.handover.deliveredBy.user:id,name',
            'financialDisbursements:id,source_id,disbursement_type,status,amount,metadata,treasury_transaction_id,paid_by_employee_id',
            'financialDisbursements.paidByEmployee:id,user_id',
            'financialDisbursements.paidByEmployee.user:id,name',
            'financialDisbursements.payments:id,financial_disbursement_id,amount,metadata',
        ]);

        $query->chunkById(150, function ($transactions) use (&$rows) {
            foreach ($transactions as $transaction) {
                if (ServiceFinancialWorkflowService::isManaged($transaction)) {
                    self::addTransaction($rows, $transaction);
                }
            }
        }, 'service_transactions.id', 'id');

        return $rows;
    }

    private static function addTransaction(array &$rows, ServiceTransaction $transaction): void
    {
        $totalHandedOver = 0.0;

        foreach ($transaction->financialReceipts as $receipt) {
            if ($receipt->status !== FinancialReceiptStatus::CONFIRMED || $receipt->isAllocatedTreasuryCash()) {
                continue;
            }

            $handedOver = 0.0;

            foreach ($receipt->handoverItems as $item) {
                $handover = $item->handover;
                if ($handover?->status !== FinancialHandoverStatus::CONFIRMED) {
                    continue;
                }

                $amount = (float) $item->confirmed_amount;
                $handedOver += $amount;
                if ($amount > 0) {
                    self::add($rows['deliverer'], $handover->delivered_by_employee_id, $handover->deliveredBy?->user?->name, [
                        'treasury_received' => $amount,
                    ]);
                }
            }

            $totalHandedOver += $handedOver;

            if ($receipt->isCash()) {
                self::add($rows['collector'], $receipt->collector_employee_id, $receipt->collector?->user?->name, [
                    'cash_received' => (float) $receipt->amount,
                    'custody' => max(0, (float) $receipt->amount - $handedOver),
                    'treasury_received' => $handedOver,
                ]);
            }
        }

        foreach ($transaction->financialDisbursements as $claim) {
            if ($claim->isReversed() || $claim->disbursement_type !== FinancialDisbursementType::EMPLOYEE_PERSONAL
                || ! in_array($claim->status, [
                    FinancialDisbursementStatus::APPROVED,
                    FinancialDisbursementStatus::PARTIALLY_REIMBURSED,
                    FinancialDisbursementStatus::FULLY_REIMBURSED,
                ], true)) {
                continue;
            }

            self::add($rows['employee'], $claim->paid_by_employee_id, $claim->paidByEmployee?->user?->name, [
                'fee_paid' => (float) $claim->amount,
                'reimbursed' => $claim->reimbursedTotal(),
                'employee_due' => $claim->remainingDue(),
            ]);
        }

        $summary = FinancialSummaryService::for(
            $transaction,
            $transaction->financialReceipts,
            $transaction->financialDisbursements,
            $totalHandedOver,
        );

        $amounts = array_intersect_key($summary, array_flip([
            'received', 'custody', 'treasury_received', 'fee_paid', 'fee_funded_remaining',
            'reimbursed', 'employee_due', 'surplus',
        ]));

        self::add($rows['company'], $transaction->company_id, $transaction->company?->trade_name, $amounts);
        self::add($rows['service'], $transaction->service_id ?? $transaction->display_service_name, $transaction->display_service_name, $amounts);
    }

    private static function add(array &$rows, int|string|null $key, ?string $name, array $amounts): void
    {
        $key ??= '';
        $rows[$key] ??= ['name' => $name, 'amounts' => []];

        foreach ($amounts as $metric => $amount) {
            $rows[$key]['amounts'][$metric] = round(($rows[$key]['amounts'][$metric] ?? 0) + $amount, 2);
        }
    }
}
