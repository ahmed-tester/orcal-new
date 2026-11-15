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
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    /**
     * Apply user filters (search, company, type, item, date range).
     */
    public static function applyFilters(Builder $query, array $filters = []): Builder
    {
        return FilterBarService::applyFilters($query, $filters, [
            'search_columns' => [
                'recipient_or_payer',
                'transaction_number',
                'voucher_number',
                'description',
                'paymentCollectionItem.sub_item',
                'paymentCollectionItem.main_item',
            ],
            'company' => true,
            'date_column' => 'transaction_date',
            'where_in' => [
                'transaction_type' => $filters['transaction_type'] ?? null,
                'payment_collection_item_id' => $filters['payment_collection_item_id'] ?? null,
            ],
            'extra' => [
                static fn (Builder $query, array $state) => static::applyPaymentItemFilter($query, $state['payment_item'] ?? null),
            ],
        ]);
    }

    /**
     * Match the payment item either through the relation or the description.
     */
    protected static function applyPaymentItemFilter(Builder $query, mixed $paymentItem): void
    {
        $items = FilterBarService::values($paymentItem);

        if ($items === []) {
            return;
        }

        $items = array_map(fn ($item) => trim((string) $item), $items);
        $items = array_values(array_filter($items, fn ($item) => $item !== ''));

        if ($items === []) {
            return;
        }

        $query->where(function (Builder $q) use ($items) {
            $q->whereHas('paymentCollectionItem', function (Builder $itemQ) use ($items) {
                $itemQ->where(function (Builder $subQ) use ($items) {
                    foreach ($items as $item) {
                        $subQ->orWhere('sub_item', $item)
                            ->orWhere('sub_item', 'like', '%"'.$item.'"%')
                            ->orWhere('sub_item', 'like', "%{$item}%");
                    }
                });
            })->orWhere(function (Builder $descQ) use ($items) {
                foreach ($items as $item) {
                    $descQ->orWhere('description', 'like', "%{$item}%");
                }
            });
        });
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
        return self::periodQuery($filters)->with(['company', 'paymentCollectionItem', 'executor.user']);
    }

    /**
     * Compute summary stats cards for the dashboard in a single fast query.
     */
    public static function getStats(array $filters = []): array
    {
        return [
            'current_balance' => self::calculateCurrentBalance($filters['company_id'] ?? null),
            ...self::movementTotals(self::periodQuery($filters)),
        ];
    }

    private static function periodQuery(array $filters, ?array $companyIds = null): Builder
    {
        $query = TreasuryTransaction::query();

        return self::applyFilters(
            $companyIds === null ? self::applyAccessScope($query) : $query->whereIn('company_id', $companyIds),
            $filters,
        );
    }

    private static function movementTotals(Builder $query): array
    {
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

        return [
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
    public static function calculateCurrentBalance(int|array|null $specificCompanyId = null): float
    {
        $user = auth()->user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;
        $authorizedIds = $user ? CompanyFormService::getAuthorizedCompanyIds($user) : [];

        if (! $isSuperAdmin && empty($authorizedIds)) {
            return 0.0;
        }

        $targetCompanyIds = collect();

        if (! empty($specificCompanyId)) {
            $ids = is_array($specificCompanyId) ? array_filter(array_map('intval', $specificCompanyId)) : [(int) $specificCompanyId];
            if (! empty($ids)) {
                $targetCompanyIds = collect($ids);
                if (! $isSuperAdmin) {
                    $targetCompanyIds = $targetCompanyIds->intersect($authorizedIds)->values();
                }
            }
        }

        if ($targetCompanyIds->isEmpty() && empty($specificCompanyId)) {
            if ($isSuperAdmin) {
                $targetCompanyIds = Company::query()->pluck('id');
            } else {
                $targetCompanyIds = collect($authorizedIds);
            }
        }

        return round((float) self::latestTransactionsQuery($targetCompanyIds)->sum('balance_after'), 2);
    }

    /**
     * Query of the latest transaction record per company.
     *
     * @param  array<int>|Collection<int, int>  $companyIds
     */
    private static function latestTransactionsQuery(array|Collection $companyIds): Builder
    {
        $ids = collect($companyIds)->filter()->values();

        if ($ids->isEmpty()) {
            return TreasuryTransaction::query()->whereRaw('1 = 0');
        }

        $latestSubQuery = TreasuryTransaction::query()
            ->selectRaw('MAX(id)')
            ->whereIn('company_id', $ids)
            ->groupBy('company_id');

        return TreasuryTransaction::query()
            ->whereIn('id', $latestSubQuery);
    }

    /**
     * Compute the Treasury metrics for the Performance Dashboard.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int>  $authorizedCompanyIds
     * @return array<string, mixed>
     */
    public static function getPerformanceStats(array $filters, array $authorizedCompanyIds): array
    {
        $user = auth()->user();
        $branchRestricted = CompanyDepartmentPerformanceService::resolveBranchId($filters, $user) !== null;
        $companyIds = self::performanceCompanyIds($filters, $authorizedCompanyIds);

        return [
            'branch_restricted' => $branchRestricted,
            'current_balance' => round((float) self::latestTransactionsQuery($companyIds)->sum('balance_after'), 2),
            ...self::movementTotals(self::periodQuery(self::performancePeriod($filters), $companyIds)),
        ];
    }

    public static function getPerformanceKashfQuery(array $filters, string $type, array $authorizedCompanyIds): Builder
    {
        $companyIds = self::performanceCompanyIds($filters, $authorizedCompanyIds);
        if ($type === 'current_balance') {
            return self::latestTransactionsQuery($companyIds)->with(['company', 'executor.user'])->orderBy('company_id');
        }

        $query = self::periodQuery(self::performancePeriod($filters), $companyIds)->with('company');

        match ($type) {
            'incoming' => $query->where('transaction_type', TreasuryTransactionType::INCOMING),
            'outgoing' => $query->where('transaction_type', TreasuryTransactionType::OUTGOING),
            'net_flow', 'all' => $query,
            default => $query->whereRaw('1 = 0'),
        };

        return $query->orderByDesc('transaction_date')->orderByDesc('id');
    }

    private static function performanceCompanyIds(array $filters, array $authorizedCompanyIds): array
    {
        $user = auth()->user();
        if (! $user?->can('viewAny', TreasuryTransaction::class)
            || CompanyDepartmentPerformanceService::resolveBranchId($filters, $user) !== null) {
            return [];
        }

        $permitted = $user->isSuperAdmin()
            ? $authorizedCompanyIds
            : array_intersect($authorizedCompanyIds, CompanyDepartmentPerformanceService::getAuthorizedCompanyIds($user));
        $selected = FilterBarService::values($filters['company_id'] ?? null);

        return array_values($selected === [] ? $permitted : array_intersect($permitted, $selected));
    }

    private static function performancePeriod(array $filters): array
    {
        return array_intersect_key($filters, array_flip(['date_from', 'date_to']));
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
