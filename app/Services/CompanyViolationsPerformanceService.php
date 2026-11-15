<?php

namespace App\Services;

use App\Enums\ViolationPaymentStatus;
use App\Enums\ViolationStage;
use App\Models\Violation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class CompanyViolationsPerformanceService
{
    /**
     * Compute the dashboard metrics from the violations register.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public static function getStats(array $filters): array
    {
        Gate::authorize('viewAny', Violation::class);

        $user = auth()->user();
        $authorizedCompanyIds = CompanyDepartmentPerformanceService::getAuthorizedCompanyIds($user);

        if ($authorizedCompanyIds === []) {
            return self::emptyStats();
        }

        $scopedCompanyIds = filled($filters['company_id'] ?? null)
            ? array_values(array_intersect($authorizedCompanyIds, (array) $filters['company_id']))
            : $authorizedCompanyIds;

        if ($scopedCompanyIds === []) {
            return self::emptyStats();
        }

        $baseQuery = self::dashboardQuery($filters);

        $totals = (clone $baseQuery)
            ->selectRaw('
                COUNT(*) as total_count,
                COALESCE(SUM(violation_amount), 0) as total_amount,
                COALESCE(SUM(paid_amount), 0) as total_paid,
                COALESCE(SUM(remaining_amount), 0) as total_remaining,
                SUM(CASE WHEN stage = ? THEN 1 ELSE 0 END) as objection_count,
                COALESCE(SUM(CASE WHEN stage = ? THEN violation_amount ELSE 0 END), 0) as objection_amount,
                SUM(CASE WHEN stage IN (?, ?) THEN 1 ELSE 0 END) as execution_count,
                COALESCE(SUM(CASE WHEN stage IN (?, ?) THEN violation_amount ELSE 0 END), 0) as execution_amount,
                SUM(CASE WHEN stage IN (?, ?) THEN 1 ELSE 0 END) as grace_count,
                COALESCE(SUM(CASE WHEN stage IN (?, ?) THEN violation_amount ELSE 0 END), 0) as grace_amount,
                SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as fully_paid_count,
                SUM(CASE WHEN paid_amount <> 0 THEN 1 ELSE 0 END) as payment_count
            ', [
                ViolationStage::OBJECTION->value,
                ViolationStage::OBJECTION->value,
                ViolationStage::ADMIN_EXECUTION->value, ViolationStage::LEGAL->value,
                ViolationStage::ADMIN_EXECUTION->value, ViolationStage::LEGAL->value,
                ViolationStage::GRACE->value, ViolationStage::CORRECTION->value,
                ViolationStage::GRACE->value, ViolationStage::CORRECTION->value,
                ViolationPaymentStatus::PAID->value,
            ])
            ->first();

        $authorities = (clone $baseQuery)
            ->selectRaw('
                issuing_authority,
                COUNT(*) as count,
                COALESCE(SUM(violation_amount), 0) as total_amount,
                COALESCE(SUM(paid_amount), 0) as paid_amount,
                COALESCE(SUM(remaining_amount), 0) as remaining_amount
            ')
            ->groupBy('issuing_authority')
            ->orderByDesc('count')
            ->orderBy('issuing_authority')
            ->get();

        return [
            'has_data' => true,
            'totals' => [
                'count' => (int) ($totals->total_count ?? 0),
                'amount' => (float) ($totals->total_amount ?? 0),
                'paid' => (float) ($totals->total_paid ?? 0),
                'remaining' => (float) ($totals->total_remaining ?? 0),
                'fully_paid_count' => (int) ($totals->fully_paid_count ?? 0),
                'payment_count' => (int) ($totals->payment_count ?? 0),
            ],
            'stages' => [
                'objection' => [
                    'count' => (int) ($totals->objection_count ?? 0),
                    'amount' => (float) ($totals->objection_amount ?? 0),
                ],
                'execution' => [
                    'count' => (int) ($totals->execution_count ?? 0),
                    'amount' => (float) ($totals->execution_amount ?? 0),
                ],
                'grace' => [
                    'count' => (int) ($totals->grace_count ?? 0),
                    'amount' => (float) ($totals->grace_amount ?? 0),
                ],
            ],
            'authorities' => $authorities,
        ];
    }

    /**
     * Query for Kashf statements in the violations domain.
     *
     * @param  array<string, mixed>  $filters
     */
    public static function getKashfQuery(
        array $filters,
        string $type = 'all',
        ?int $companyId = null
    ): Builder {
        Gate::authorize('viewAny', Violation::class);

        $query = self::dashboardQuery($filters)
            ->with(['company', 'category', 'type'])
            ->when($companyId !== null, fn (Builder $query) => $query->where('company_id', $companyId))
            ->orderByDesc('violation_date')
            ->orderByDesc('id');

        return match ($type) {
            'objection' => $query->where('stage', ViolationStage::OBJECTION->value),
            'execution' => $query->whereIn('stage', [ViolationStage::ADMIN_EXECUTION->value, ViolationStage::LEGAL->value]),
            'grace' => $query->whereIn('stage', [ViolationStage::GRACE->value, ViolationStage::CORRECTION->value]),
            'paid' => $query->where('payment_status', ViolationPaymentStatus::PAID->value),
            'payments' => $query->where('paid_amount', '<>', 0),
            'unpaid' => $query->where('remaining_amount', '<>', 0),
            'all' => $query,
            default => $query->whereRaw('1 = 0'),
        };
    }

    private static function dashboardQuery(array $filters): Builder
    {
        return ViolationSummaryService::filtered(array_intersect_key($filters, array_flip([
            'company_id', 'date_from', 'date_to',
        ])));
    }

    /**
     * @return array<string, mixed>
     */
    private static function emptyStats(): array
    {
        return [
            'has_data' => false,
            'totals' => ['count' => 0, 'amount' => 0, 'paid' => 0, 'remaining' => 0, 'fully_paid_count' => 0, 'payment_count' => 0],
            'stages' => [
                'objection' => ['count' => 0, 'amount' => 0],
                'execution' => ['count' => 0, 'amount' => 0],
                'grace' => ['count' => 0, 'amount' => 0],
            ],
            'authorities' => collect(),
        ];
    }
}
