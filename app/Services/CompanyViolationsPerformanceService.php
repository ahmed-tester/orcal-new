<?php

namespace App\Services;

use App\Enums\ViolationPaymentStatus;
use App\Enums\ViolationStage;
use App\Models\Company;
use App\Models\Violation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CompanyViolationsPerformanceService
{
    /**
     * Compute violations metrics, Efaa issuing authorities breakdown,
     * and vehicle authorizations status.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public static function getStats(array $filters): array
    {
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

        [$startDate, $endDate] = CompanyDepartmentPerformanceService::resolveDateRange($filters);

        // 1. Violations aggregation by stage and payment status
        $baseQuery = Violation::query()
            ->whereIn('company_id', $scopedCompanyIds);

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
                SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as partial_count,
                SUM(CASE WHEN payment_status = ? THEN 1 ELSE 0 END) as pending_count
            ', [
                ViolationStage::OBJECTION->value,
                ViolationStage::OBJECTION->value,
                ViolationStage::ADMIN_EXECUTION->value, ViolationStage::LEGAL->value,
                ViolationStage::ADMIN_EXECUTION->value, ViolationStage::LEGAL->value,
                ViolationStage::GRACE->value, ViolationStage::CORRECTION->value,
                ViolationStage::GRACE->value, ViolationStage::CORRECTION->value,
                ViolationPaymentStatus::PAID->value,
                ViolationPaymentStatus::PARTIAL->value,
                ViolationPaymentStatus::PENDING->value,
            ])
            ->first();

        // 2. Efaa & Issuing Authorities Breakdown
        $authorities = (clone $baseQuery)
            ->selectRaw('
                issuing_authority,
                COUNT(*) as count,
                COALESCE(SUM(violation_amount), 0) as total_amount,
                COALESCE(SUM(paid_amount), 0) as paid_amount,
                COALESCE(SUM(remaining_amount), 0) as remaining_amount
            ')
            ->whereNotNull('issuing_authority')
            ->groupBy('issuing_authority')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // 3. Vehicles & transport violations (from companies metadata)
        $companies = Company::query()
            ->whereIn('id', $scopedCompanyIds)
            ->get();

        $vehicleStats = [
            'total_vehicles' => 0,
            'authorized_vehicles' => 0,
            'unauthorized_vehicles' => 0,
            'traffic_violations_count' => 0,
            'transport_violations_count' => 0,
            'has_registered_vehicles' => false,
        ];

        foreach ($companies as $comp) {
            $metaVehicles = is_array($comp->metadata['vehicles'] ?? null) ? $comp->metadata['vehicles'] : [];
            if (! empty($metaVehicles)) {
                $vehicleStats['has_registered_vehicles'] = true;
                foreach ($metaVehicles as $v) {
                    $vehicleStats['total_vehicles']++;
                    if (! empty($v['is_authorized'])) {
                        $vehicleStats['authorized_vehicles']++;
                    } else {
                        $vehicleStats['unauthorized_vehicles']++;
                    }
                    $vehicleStats['traffic_violations_count'] += (int) ($v['traffic_violations'] ?? 0);
                    $vehicleStats['transport_violations_count'] += (int) ($v['transport_violations'] ?? 0);
                }
            }
        }

        return [
            'has_data' => true,
            'totals' => [
                'count' => (int) ($totals->total_count ?? 0),
                'amount' => (float) ($totals->total_amount ?? 0),
                'paid' => (float) ($totals->total_paid ?? 0),
                'remaining' => (float) ($totals->total_remaining ?? 0),
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
                'paid' => [
                    'count' => (int) ($totals->paid_count ?? 0),
                    'amount' => (float) ($totals->total_paid ?? 0),
                ],
            ],
            'authorities' => $authorities,
            'vehicles' => $vehicleStats,
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
        $user = auth()->user();
        $authorizedCompanyIds = CompanyDepartmentPerformanceService::getAuthorizedCompanyIds($user);

        $targetCompanyIds = $companyId !== null
            ? array_values(array_intersect($authorizedCompanyIds, [$companyId]))
            : (filled($filters['company_id'] ?? null)
                ? array_values(array_intersect($authorizedCompanyIds, (array) $filters['company_id']))
                : $authorizedCompanyIds);

        $query = Violation::query()
            ->with(['company', 'category', 'type'])
            ->whereIn('company_id', $targetCompanyIds ?: [0])
            ->orderByDesc('violation_date');

        return match ($type) {
            'objection' => $query->where('stage', ViolationStage::OBJECTION->value),
            'execution' => $query->whereIn('stage', [ViolationStage::ADMIN_EXECUTION->value, ViolationStage::LEGAL->value]),
            'grace' => $query->whereIn('stage', [ViolationStage::GRACE->value, ViolationStage::CORRECTION->value]),
            'paid' => $query->where('payment_status', ViolationPaymentStatus::PAID->value),
            'unpaid' => $query->where('remaining_amount', '>', 0),
            default => $query,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function emptyStats(): array
    {
        return [
            'has_data' => false,
            'totals' => ['count' => 0, 'amount' => 0, 'paid' => 0, 'remaining' => 0],
            'stages' => [
                'objection' => ['count' => 0, 'amount' => 0],
                'execution' => ['count' => 0, 'amount' => 0],
                'grace' => ['count' => 0, 'amount' => 0],
                'paid' => ['count' => 0, 'amount' => 0],
            ],
            'authorities' => collect(),
            'vehicles' => [
                'total_vehicles' => 0,
                'authorized_vehicles' => 0,
                'unauthorized_vehicles' => 0,
                'traffic_violations_count' => 0,
                'transport_violations_count' => 0,
                'has_registered_vehicles' => false,
            ],
        ];
    }
}
