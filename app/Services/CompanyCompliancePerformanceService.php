<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CompanyCompliancePerformanceService
{
    /**
     * Compute compliance metrics: Iqama expiries, work permits, medical insurance,
     * official company licenses, and platform subscriptions.
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

        $branchId = CompanyDepartmentPerformanceService::resolveBranchId($filters, $user);
        $teamIds = CompanyDepartmentPerformanceService::isSupervisorViewer($user)
            ? CompanyDepartmentPerformanceService::supervisorTeamIds(
                $user,
                ...CompanyDepartmentPerformanceService::resolveDateRange($filters)
            )
            : null;

        $today = Carbon::today()->toDateString();
        $in3Months = Carbon::today()->addDays(90)->toDateString();
        $in6Months = Carbon::today()->addDays(180)->toDateString();
        $in9Months = Carbon::today()->addDays(270)->toDateString();
        $in30Days = Carbon::today()->addDays(30)->toDateString();

        // 1. Employee documents aggregation (Iqama, Work Permit, Insurance)
        $employeeQuery = Employee::query()
            ->join('employee_details', 'employees.id', '=', 'employee_details.employee_id')
            ->whereIn('employees.company_id', $scopedCompanyIds)
            ->where('employees.status', 'active');

        if ($branchId !== null) {
            $employeeQuery->where('employees.branch_id', $branchId);
        }

        if ($teamIds !== null) {
            $employeeQuery->whereIn('employees.id', $teamIds);
        }

        $empStats = (clone $employeeQuery)
            ->selectRaw('
                COUNT(*) as total_active_employees,
                SUM(CASE WHEN employee_details.iqama_expiry IS NOT NULL AND employee_details.iqama_expiry < ? THEN 1 ELSE 0 END) as iqama_expired,
                SUM(CASE WHEN employee_details.iqama_expiry >= ? AND employee_details.iqama_expiry <= ? THEN 1 ELSE 0 END) as iqama_within_3m,
                SUM(CASE WHEN employee_details.iqama_expiry > ? AND employee_details.iqama_expiry <= ? THEN 1 ELSE 0 END) as iqama_within_6m,
                SUM(CASE WHEN employee_details.iqama_expiry > ? AND employee_details.iqama_expiry <= ? THEN 1 ELSE 0 END) as iqama_within_9m,
                SUM(CASE WHEN employee_details.iqama_expiry > ? THEN 1 ELSE 0 END) as iqama_valid_over_9m,
                SUM(CASE WHEN employee_details.iqama_expiry IS NOT NULL THEN 1 ELSE 0 END) as total_iqamas_tracked,
                SUM(CASE WHEN employee_details.work_permit_expiry IS NOT NULL AND employee_details.work_permit_expiry < ? THEN 1 ELSE 0 END) as work_permit_expired,
                SUM(CASE WHEN employee_details.work_permit_expiry >= ? AND employee_details.work_permit_expiry <= ? THEN 1 ELSE 0 END) as work_permit_expiring_soon,
                SUM(CASE WHEN employee_details.insurance_expiry IS NOT NULL AND employee_details.insurance_expiry < ? THEN 1 ELSE 0 END) as insurance_expired,
                SUM(CASE WHEN employee_details.insurance_expiry >= ? AND employee_details.insurance_expiry <= ? THEN 1 ELSE 0 END) as insurance_expiring_soon
            ', [
                $today,
                $today, $in3Months,
                $in3Months, $in6Months,
                $in6Months, $in9Months,
                $in9Months,
                $today,
                $today, $in3Months,
                $today,
                $today, $in3Months,
            ])
            ->first();

        // 2. Company Licenses (Municipality, Civil Defense, Commercial Registration)
        $companies = Company::query()
            ->whereIn('id', $scopedCompanyIds)
            ->get();

        $licenses = [
            'municipality' => ['expired' => 0, 'expiring_soon' => 0, 'valid' => 0, 'unspecified' => 0],
            'civil_defense' => ['expired' => 0, 'expiring_soon' => 0, 'valid' => 0, 'unspecified' => 0],
            'commercial_reg' => ['expired' => 0, 'expiring_soon' => 0, 'valid' => 0, 'unspecified' => 0],
        ];

        $subscriptions = [
            'qiwa' => ['active' => 0, 'expired' => 0, 'unspecified' => 0],
            'absher' => ['active' => 0, 'expired' => 0, 'unspecified' => 0],
            'spl' => ['active' => 0, 'expired' => 0, 'unspecified' => 0],
        ];

        $todayCarbon = Carbon::today();
        $in30DaysCarbon = Carbon::today()->addDays(30);

        foreach ($companies as $company) {
            self::classifyLicense($company->municipality_license_expiry, $todayCarbon, $in30DaysCarbon, $licenses['municipality']);
            self::classifyLicense($company->civil_defense_license_expiry, $todayCarbon, $in30DaysCarbon, $licenses['civil_defense']);
            self::classifyLicense($company->commercial_registration_expiry, $todayCarbon, $in30DaysCarbon, $licenses['commercial_reg']);

            $metaSubs = is_array($company->metadata['subscriptions'] ?? null) ? $company->metadata['subscriptions'] : [];
            self::classifySubscription($metaSubs['qiwa'] ?? null, $todayCarbon, $subscriptions['qiwa']);
            self::classifySubscription($metaSubs['absher'] ?? null, $todayCarbon, $subscriptions['absher']);
            self::classifySubscription($metaSubs['spl'] ?? null, $todayCarbon, $subscriptions['spl']);
        }

        return [
            'has_data' => true,
            'companies_count' => $companies->count(),
            'iqama' => [
                'expired' => (int) ($empStats->iqama_expired ?? 0),
                'within_3m' => (int) ($empStats->iqama_within_3m ?? 0),
                'within_6m' => (int) ($empStats->iqama_within_6m ?? 0),
                'within_9m' => (int) ($empStats->iqama_within_9m ?? 0),
                'valid_over_9m' => (int) ($empStats->iqama_valid_over_9m ?? 0),
                'total_tracked' => (int) ($empStats->total_iqamas_tracked ?? 0),
            ],
            'work_permits' => [
                'expired' => (int) ($empStats->work_permit_expired ?? 0),
                'expiring_soon' => (int) ($empStats->work_permit_expiring_soon ?? 0),
            ],
            'insurance' => [
                'expired' => (int) ($empStats->insurance_expired ?? 0),
                'expiring_soon' => (int) ($empStats->insurance_expiring_soon ?? 0),
            ],
            'licenses' => $licenses,
            'subscriptions' => $subscriptions,
        ];
    }

    /**
     * Query for Kashf statements in the compliance domain (e.g. employee list by expiry status).
     *
     * @param  array<string, mixed>  $filters
     */
    public static function getKashfQuery(
        array $filters,
        string $type = 'iqama_expired',
        ?int $companyId = null,
        int|string|null $departmentId = null
    ): Builder {
        $user = auth()->user();
        $authorizedCompanyIds = CompanyDepartmentPerformanceService::getAuthorizedCompanyIds($user);

        $targetCompanyIds = $companyId !== null
            ? array_values(array_intersect($authorizedCompanyIds, [$companyId]))
            : (filled($filters['company_id'] ?? null)
                ? array_values(array_intersect($authorizedCompanyIds, (array) $filters['company_id']))
                : $authorizedCompanyIds);

        $branchId = CompanyDepartmentPerformanceService::resolveBranchId($filters, $user);
        $teamIds = CompanyDepartmentPerformanceService::isSupervisorViewer($user)
            ? CompanyDepartmentPerformanceService::supervisorTeamIds(
                $user,
                ...CompanyDepartmentPerformanceService::resolveDateRange($filters)
            )
            : null;

        $query = Employee::query()
            ->with(['user', 'company', 'branch', 'department', 'details'])
            ->join('employee_details', 'employees.id', '=', 'employee_details.employee_id')
            ->whereIn('employees.company_id', $targetCompanyIds ?: [0])
            ->where('employees.status', 'active')
            ->select('employees.*');

        if ($branchId !== null) {
            $query->where('employees.branch_id', $branchId);
        }

        if ($teamIds !== null) {
            $query->whereIn('employees.id', $teamIds);
        }

        if ($departmentId !== null) {
            if ($departmentId === 'unassigned' || $departmentId === 0) {
                $query->whereNull('employees.department_id');
            } else {
                $query->where('employees.department_id', (int) $departmentId);
            }
        }

        $today = Carbon::today()->toDateString();
        $in3Months = Carbon::today()->addDays(90)->toDateString();
        $in6Months = Carbon::today()->addDays(180)->toDateString();
        $in9Months = Carbon::today()->addDays(270)->toDateString();

        return match ($type) {
            'iqama_expired' => $query->whereNotNull('employee_details.iqama_expiry')
                ->where('employee_details.iqama_expiry', '<', $today)
                ->orderBy('employee_details.iqama_expiry', 'asc'),

            'iqama_within_3m' => $query->whereNotNull('employee_details.iqama_expiry')
                ->where('employee_details.iqama_expiry', '>=', $today)
                ->where('employee_details.iqama_expiry', '<=', $in3Months)
                ->orderBy('employee_details.iqama_expiry', 'asc'),

            'iqama_within_6m' => $query->whereNotNull('employee_details.iqama_expiry')
                ->where('employee_details.iqama_expiry', '>', $in3Months)
                ->where('employee_details.iqama_expiry', '<=', $in6Months)
                ->orderBy('employee_details.iqama_expiry', 'asc'),

            'iqama_within_9m' => $query->whereNotNull('employee_details.iqama_expiry')
                ->where('employee_details.iqama_expiry', '>', $in6Months)
                ->where('employee_details.iqama_expiry', '<=', $in9Months)
                ->orderBy('employee_details.iqama_expiry', 'asc'),

            'iqama_valid_over_9m' => $query->whereNotNull('employee_details.iqama_expiry')
                ->where('employee_details.iqama_expiry', '>', $in9Months)
                ->orderBy('employee_details.iqama_expiry', 'asc'),

            'work_permit_expired' => $query->whereNotNull('employee_details.work_permit_expiry')
                ->where('employee_details.work_permit_expiry', '<', $today)
                ->orderBy('employee_details.work_permit_expiry', 'asc'),

            'insurance_expired' => $query->whereNotNull('employee_details.insurance_expiry')
                ->where('employee_details.insurance_expiry', '<', $today)
                ->orderBy('employee_details.insurance_expiry', 'asc'),

            default => $query->whereNotNull('employee_details.iqama_expiry')
                ->orderBy('employee_details.iqama_expiry', 'asc'),
        };
    }

    /**
     * @param  array<string, int>  $bucket
     */
    private static function classifyLicense(?Carbon $date, Carbon $today, Carbon $in30Days, array &$bucket): void
    {
        if ($date === null) {
            $bucket['unspecified']++;
            return;
        }

        if ($date->lt($today)) {
            $bucket['expired']++;
        } elseif ($date->lte($in30Days)) {
            $bucket['expiring_soon']++;
        } else {
            $bucket['valid']++;
        }
    }

    /**
     * @param  array<string, mixed>|null  $sub
     * @param  array<string, int>  $bucket
     */
    private static function classifySubscription(?array $sub, Carbon $today, array &$bucket): void
    {
        if ($sub === null || empty($sub['expiry_date'])) {
            $bucket['unspecified']++;
            return;
        }

        $expiry = Carbon::parse($sub['expiry_date']);
        if ($expiry->lt($today)) {
            $bucket['expired']++;
        } else {
            $bucket['active']++;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function emptyStats(): array
    {
        return [
            'has_data' => false,
            'companies_count' => 0,
            'iqama' => [
                'expired' => 0,
                'within_3m' => 0,
                'within_6m' => 0,
                'within_9m' => 0,
                'valid_over_9m' => 0,
                'total_tracked' => 0,
            ],
            'work_permits' => ['expired' => 0, 'expiring_soon' => 0],
            'insurance' => ['expired' => 0, 'expiring_soon' => 0],
            'licenses' => [
                'municipality' => ['expired' => 0, 'expiring_soon' => 0, 'valid' => 0, 'unspecified' => 0],
                'civil_defense' => ['expired' => 0, 'expiring_soon' => 0, 'valid' => 0, 'unspecified' => 0],
                'commercial_reg' => ['expired' => 0, 'expiring_soon' => 0, 'valid' => 0, 'unspecified' => 0],
            ],
            'subscriptions' => [
                'qiwa' => ['active' => 0, 'expired' => 0, 'unspecified' => 0],
                'absher' => ['active' => 0, 'expired' => 0, 'unspecified' => 0],
                'spl' => ['active' => 0, 'expired' => 0, 'unspecified' => 0],
            ],
        ];
    }
}
