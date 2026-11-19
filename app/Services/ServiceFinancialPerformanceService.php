<?php

namespace App\Services;

use App\Enums\FinancialDisbursementStatus;
use App\Enums\FinancialDisbursementType;
use App\Enums\FinancialHandoverStatus;
use App\Enums\FinancialPaymentMethod;
use App\Filament\Resources\ServiceTransactions\ServiceTransactionResource;
use App\Models\CashHandover;
use App\Models\FinancialDisbursement;
use App\Models\FinancialDisbursementPayment;
use App\Models\FinancialReceipt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ServiceFinancialPerformanceService
{
    public const TRANSFER_METRICS = ['receipt_cash', 'receipt_non_cash', 'custody', 'handover_pending', 'handover_confirmed'];

    public const SETTLEMENT_METRICS = ['fee_direct', 'fee_personal', 'claim_pending', 'reimbursement_paid', 'reimbursement_due'];

    public static function query(string $type, array $filters, array $companyIds): Builder
    {
        abort_unless(ServiceTransactionResource::canViewAny(), 403);

        return match ($type) {
            'receipt_cash', 'receipt_non_cash', 'custody' => self::receiptQuery($type, $filters, $companyIds),
            'handover_pending', 'handover_confirmed' => self::handoverQuery($type, $filters, $companyIds),
            'fee_direct', 'fee_personal', 'claim_pending', 'reimbursement_due' => self::claimQuery($type, $filters, $companyIds),
            'reimbursement_paid' => self::paymentQuery($filters, $companyIds),
            default => abort(404),
        };
    }

    private static function receiptQuery(string $type, array $filters, array $companyIds): Builder
    {
        $query = self::serviceDocuments(FinancialReceipt::query(), $filters, $companyIds)->confirmed();

        if ($type !== 'custody') {
            $query->where('payment_method', $type === 'receipt_cash' ? '=' : '<>', FinancialPaymentMethod::CASH->value);

            return self::period($query, $filters, 'confirmed_at')
                ->select('financial_receipts.*')->selectRaw('amount as dashboard_amount');
        }

        $query->cash()->whereNull('metadata->cash_handover_id')
            ->withSum('confirmedHandoverItems as dashboard_handed_over', 'confirmed_amount')
            ->withSum('reservedHandoverItems as dashboard_reserved', 'requested_amount');

        return FinancialReceipt::query()->fromSub($query->toBase(), 'financial_receipts')
            ->whereRaw('amount > COALESCE(dashboard_handed_over, 0)')
            ->select('financial_receipts.*')
            ->selectRaw('amount - COALESCE(dashboard_handed_over, 0) as dashboard_amount');
    }

    private static function handoverQuery(string $type, array $filters, array $companyIds): Builder
    {
        $receipts = self::serviceDocuments(FinancialReceipt::query(), $filters, $companyIds)->confirmed();
        $query = self::documentScope(CashHandover::query(), $filters, $companyIds)
            ->whereHas('items')
            ->whereDoesntHave('items', fn (Builder $items) => $items->whereNotIn('financial_receipt_id',
                $receipts->select('financial_receipts.id')->whereColumn('financial_receipts.company_id', 'cash_handovers.company_id')->toBase()))
            ->select('cash_handovers.*');

        if ($type === 'handover_pending') {
            return $query->where('cash_handovers.status', FinancialHandoverStatus::SUBMITTED->value)
                ->selectRaw('total_requested_amount as dashboard_amount');
        }

        return self::period($query->confirmed(), $filters, 'cash_handovers.confirmed_at')
            ->join('treasury_transactions', function ($join) {
                $join->on('treasury_transactions.id', '=', 'cash_handovers.treasury_transaction_id')
                    ->on('treasury_transactions.company_id', '=', 'cash_handovers.company_id');
            })->selectRaw('treasury_transactions.amount as dashboard_amount');
    }

    private static function claimQuery(string $type, array $filters, array $companyIds): Builder
    {
        $query = self::claims($filters, $companyIds)->select('financial_disbursements.*');

        if ($type === 'fee_direct') {
            return self::period($query->where('disbursement_type', FinancialDisbursementType::TREASURY_DIRECT->value)
                ->join('treasury_transactions', function ($join) {
                    $join->on('treasury_transactions.id', '=', 'financial_disbursements.treasury_transaction_id')
                        ->on('treasury_transactions.company_id', '=', 'financial_disbursements.company_id');
                }), $filters, 'treasury_transactions.transaction_date')
                ->selectRaw('financial_disbursements.amount as dashboard_amount, treasury_transactions.transaction_date as dashboard_date');
        }

        $query->where('disbursement_type', FinancialDisbursementType::EMPLOYEE_PERSONAL->value);

        if ($type === 'reimbursement_due') {
            $query->whereIn('status', FinancialSummaryService::OPEN_APPROVED_CLAIM_STATUSES)
                ->withSum(['payments as dashboard_reimbursed' => fn (Builder $payments) => $payments
                    ->whereNull('financial_disbursement_payments.metadata->reversed_at')
                    ->whereColumn('financial_disbursement_payments.company_id', 'financial_disbursements.company_id')], 'amount');

            return FinancialDisbursement::query()->fromSub($query->toBase(), 'financial_disbursements')
                ->whereRaw('amount > COALESCE(dashboard_reimbursed, 0)')
                ->select('financial_disbursements.*')
                ->selectRaw('amount - COALESCE(dashboard_reimbursed, 0) as dashboard_amount');
        }

        $query = $type === 'fee_personal'
            ? self::period($query->whereIn('status', FinancialSummaryService::APPROVED_CLAIM_STATUSES), $filters, 'approved_at')
            : $query->where('status', FinancialDisbursementStatus::PENDING_APPROVAL->value);

        return $query->selectRaw('financial_disbursements.amount as dashboard_amount');
    }

    private static function paymentQuery(array $filters, array $companyIds): Builder
    {
        $claims = self::claims($filters, $companyIds)
            ->where('disbursement_type', FinancialDisbursementType::EMPLOYEE_PERSONAL->value)
            ->whereColumn('financial_disbursements.company_id', 'financial_disbursement_payments.company_id')
            ->select('financial_disbursements.id');

        return self::period(self::documentScope(FinancialDisbursementPayment::query(), $filters, $companyIds)
            ->whereIn('financial_disbursement_id', $claims->toBase())
            ->whereNull('metadata->reversed_at'), $filters, 'paid_at')
            ->select('financial_disbursement_payments.*')->selectRaw('amount as dashboard_amount');
    }

    private static function claims(array $filters, array $companyIds): Builder
    {
        return self::serviceDocuments(FinancialDisbursement::query(), $filters, $companyIds)
            ->whereNull('financial_disbursements.metadata->reversed_at');
    }

    private static function serviceDocuments(Builder $query, array $filters, array $companyIds): Builder
    {
        $table = $query->getModel()->getTable();
        $source = ServiceTransactionResource::getEloquentQuery()
            ->where('service_transactions.metadata->'.ServiceFinancialWorkflowService::FLOW_KEY, ServiceFinancialWorkflowService::WORKFLOW_VERSION)
            ->whereColumn('service_transactions.company_id', $table.'.company_id')
            ->reorder()->select('service_transactions.id');

        return self::documentScope($query, $filters, $companyIds)
            ->where($table.'.source_type', FinancialSummaryService::SOURCE_TYPE)
            ->whereIn($table.'.source_id', $source->toBase());
    }

    private static function documentScope(Builder $query, array $filters, array $companyIds): Builder
    {
        $table = $query->getModel()->getTable();
        $query->whereIn($table.'.company_id', $companyIds);
        $branch = $filters['branch_id'] ?? null;

        return $query->when($branch !== null, fn (Builder $builder) => $builder->where($table.'.branch_id', $branch));
    }

    private static function period(Builder $query, array $filters, string $column): Builder
    {
        return FilterBarService::applyFilters($query, $filters, ['date_column' => $column]);
    }

    public static function total(Builder $query): float
    {
        return round((float) DB::query()->fromSub($query->toBase(), 'financial_rows')->sum('dashboard_amount'), 2);
    }
}
