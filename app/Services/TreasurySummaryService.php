<?php

namespace App\Services;

use App\Enums\TreasuryTransactionType;
use App\Models\Company;
use App\Models\TreasuryTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TreasurySummaryService
{
    /**
     * Apply security & multi-company access scope.
     */
    public static function applyAccessScope(Builder $query): Builder
    {
        $user = auth()->user();

        if (! $user || $user->isSuperAdmin()) {
            return $query;
        }

        $companyIds = CompanyFormService::getAuthorizedCompanyIds($user);

        if (! empty($companyIds)) {
            $query->whereIn('company_id', $companyIds);
        }

        return $query;
    }

    /**
     * Apply user filters (search, company, type, item, date range).
     */
    public static function applyFilters(Builder $query, array $filters = []): Builder
    {
        if (! empty($filters['search'])) {
            $term = trim($filters['search']);
            $query->where(function (Builder $q) use ($term) {
                $q->where('recipient_or_payer', 'like', "%{$term}%")
                    ->orWhere('transaction_number', 'like', "%{$term}%")
                    ->orWhere('voucher_number', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhereHas('paymentCollectionItem', function (Builder $itemQ) use ($term) {
                        $itemQ->where('sub_item', 'like', "%{$term}%")
                            ->orWhere('main_item', 'like', "%{$term}%");
                    });
            });
        }

        if (! empty($filters['company_id'])) {
            $query->where('company_id', (int) $filters['company_id']);
        }

        if (! empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        if (! empty($filters['payment_collection_item_id'])) {
            $query->where('payment_collection_item_id', (int) $filters['payment_collection_item_id']);
        }

        if (! empty($filters['payment_item'])) {
            $item = trim((string) $filters['payment_item']);
            $query->whereHas('paymentCollectionItem', function (Builder $itemQ) use ($item) {
                $itemQ->where('sub_item', $item);
            });
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('transaction_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('transaction_date', '<=', $filters['date_to']);
        }

        return $query;
    }

    /**
     * Grouped query: One row per Beneficiary / Person (Strict MySQL ONLY_FULL_GROUP_BY compatible).
     */
    public static function summaryQuery(array $filters = []): Builder
    {
        $query = TreasuryTransaction::query()
            ->selectRaw("
                MIN(id) as row_id,
                COALESCE(NULLIF(TRIM(recipient_or_payer), ''), 'طرف غير محدد') as beneficiary_name,
                recipient_or_payer,
                company_id,
                COUNT(*) as transactions_count,
                COALESCE(SUM(CASE WHEN transaction_type = 'incoming' THEN amount ELSE 0 END), 0) as total_incoming,
                COALESCE(SUM(CASE WHEN transaction_type = 'outgoing' THEN amount ELSE 0 END), 0) as total_outgoing,
                COALESCE(SUM(CASE WHEN transaction_type = 'incoming' THEN amount ELSE -amount END), 0) as net_amount,
                MAX(transaction_date) as last_transaction_date
            ")
            ->groupBy('recipient_or_payer', 'company_id')
            ->with(['company']);

        self::applyAccessScope($query);
        self::applyFilters($query, $filters);

        return $query;
    }

    /**
     * Flat transactions query for detailed views.
     */
    public static function transactionsQuery(array $filters = []): Builder
    {
        $query = TreasuryTransaction::query()
            ->with(['company', 'paymentCollectionItem', 'executor.user']);

        self::applyAccessScope($query);
        self::applyFilters($query, $filters);

        return $query;
    }

    /**
     * Compute summary stats cards for the dashboard in a single fast query.
     */
    public static function getStats(array $filters = []): array
    {
        $query = TreasuryTransaction::query();
        self::applyAccessScope($query);
        self::applyFilters($query, $filters);

        $aggregates = $query->selectRaw("
            COUNT(*) as total_count,
            COALESCE(SUM(CASE WHEN transaction_type = 'incoming' THEN amount ELSE 0 END), 0) as total_incoming,
            COALESCE(SUM(CASE WHEN transaction_type = 'outgoing' THEN amount ELSE 0 END), 0) as total_outgoing,
            COUNT(DISTINCT recipient_or_payer) as beneficiaries_count
        ")->first();

        $totalIncoming = (float) ($aggregates->total_incoming ?? 0);
        $totalOutgoing = (float) ($aggregates->total_outgoing ?? 0);
        $netFlow = $totalIncoming - $totalOutgoing;
        $totalCount = (int) ($aggregates->total_count ?? 0);
        $beneficiariesCount = (int) ($aggregates->beneficiaries_count ?? 0);

        // Calculate real-time current balance
        $currentBalance = self::calculateCurrentBalance($filters['company_id'] ?? null);

        return [
            'current_balance' => round($currentBalance, 2),
            'total_incoming' => round($totalIncoming, 2),
            'total_outgoing' => round($totalOutgoing, 2),
            'net_flow' => round($netFlow, 2),
            'beneficiaries_count' => $beneficiariesCount,
            'total_count' => $totalCount,
        ];
    }

    /**
     * Calculate current actual treasury balance (sum of latest balance_after per authorized company).
     */
    public static function calculateCurrentBalance(?int $specificCompanyId = null): float
    {
        if ($specificCompanyId) {
            $latest = TreasuryTransaction::where('company_id', $specificCompanyId)
                ->orderBy('id', 'desc')
                ->value('balance_after');

            return (float) ($latest ?? 0);
        }

        $user = auth()->user();
        $authorizedIds = CompanyFormService::getAuthorizedCompanyIds($user);

        $companyQuery = Company::query();
        if (! empty($authorizedIds)) {
            $companyQuery->whereIn('id', $authorizedIds);
        }

        $targetCompanyIds = $companyQuery->pluck('id');

        $total = 0.0;
        if ($targetCompanyIds->isNotEmpty()) {
            $latestIds = TreasuryTransaction::whereIn('company_id', $targetCompanyIds)
                ->selectRaw('MAX(id) as id')
                ->groupBy('company_id')
                ->pluck('id');

            if ($latestIds->isNotEmpty()) {
                $total = (float) TreasuryTransaction::whereIn('id', $latestIds)->sum('balance_after');
            }
        }

        return $total;
    }

    /**
     * Modal payload: Fetch all transactions for this specific beneficiary.
     */
    public static function getBeneficiaryDetails(string $beneficiaryName, ?int $companyId = null, array $filters = []): ?array
    {
        $query = TreasuryTransaction::query()
            ->with(['company', 'paymentCollectionItem', 'executor.user']);

        if ($beneficiaryName === 'طرف غير محدد') {
            $query->where(fn ($q) => $q->whereNull('recipient_or_payer')->orWhere('recipient_or_payer', ''));
        } else {
            $query->where('recipient_or_payer', $beneficiaryName);
        }

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        self::applyAccessScope($query);
        self::applyFilters($query, $filters);

        $transactions = $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc')->get();

        if ($transactions->isEmpty()) {
            return null;
        }

        $first = $transactions->first();

        $incoming = (float) $transactions->filter(function ($t) {
            return ($t->transaction_type instanceof TreasuryTransactionType)
                ? $t->transaction_type === TreasuryTransactionType::INCOMING
                : $t->transaction_type === 'incoming';
        })->sum('amount');

        $outgoing = (float) $transactions->filter(function ($t) {
            return ($t->transaction_type instanceof TreasuryTransactionType)
                ? $t->transaction_type === TreasuryTransactionType::OUTGOING
                : $t->transaction_type === 'outgoing';
        })->sum('amount');

        return [
            'beneficiary_name' => $beneficiaryName,
            'company_name' => $first->company?->trade_name ?? '—',
            'company_id' => $first->company_id,
            'transactions_count' => $transactions->count(),
            'total_incoming' => $incoming,
            'total_outgoing' => $outgoing,
            'net_amount' => $incoming - $outgoing,
            'transactions' => $transactions,
        ];
    }

    /**
     * Export grouped beneficiary rows as CSV.
     */
    public static function exportRows(array $filters = []): Collection
    {
        return self::summaryQuery($filters)
            ->orderBy('total_outgoing', 'desc')
            ->get();
    }
}
