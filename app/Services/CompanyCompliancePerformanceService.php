<?php

namespace App\Services;

use App\Enums\UserType;
use App\Models\Company;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class CompanyCompliancePerformanceService
{
    public const LICENSE_WARNING_DAYS = 30;

    public const DOCUMENT_WARNING_DAYS = 90;

    private const LICENSE_FIELDS = [
        'municipality' => 'municipality_license_expiry',
        'civil_defense' => 'civil_defense_license_expiry',
        'commercial_reg' => 'commercial_registration_expiry',
    ];

    private const LICENSE_STATUSES = ['valid', 'expiring_soon', 'expired', 'unspecified'];

    public static function canView(): bool
    {
        return Gate::allows('viewAny', Employee::class) || Gate::allows('viewAny', Company::class);
    }

    public static function getStats(array $filters): array
    {
        $companyIds = self::companyIds($filters);
        $stats = [
            'has_data' => $companyIds !== [] && self::canView(),
            'iqama' => null,
            'work_permits' => null,
            'insurance' => null,
            'licenses' => null,
            'subscriptions' => null,
        ];

        if (Gate::allows('viewAny', Employee::class)) {
            $counts = self::countBuckets(self::employeeQuery($filters, $companyIds), self::documentBuckets());
            $stats['iqama'] = [
                'expired' => $counts['iqama_expired'],
                'within_3m' => $counts['iqama_within_3m'],
                'within_6m' => $counts['iqama_within_6m'],
                'within_9m' => $counts['iqama_within_9m'],
                'valid_over_9m' => $counts['iqama_valid_over_9m'],
            ];
            $stats['iqama']['total_tracked'] = array_sum($stats['iqama']);
            $stats['work_permits'] = [
                'expired' => $counts['work_permit_expired'],
                'expiring_soon' => $counts['work_permit_expiring_soon'],
                'valid' => $counts['work_permit_valid'],
            ];
            $stats['insurance'] = [
                'expired' => $counts['insurance_expired'],
                'expiring_soon' => $counts['insurance_expiring_soon'],
                'valid' => $counts['insurance_valid'],
            ];
        }

        if (Gate::allows('viewAny', Company::class)) {
            $companies = self::companyQuery($companyIds);
            $counts = self::countBuckets($companies, self::licenseBuckets());
            foreach (array_keys(self::LICENSE_FIELDS) as $license) {
                foreach (self::LICENSE_STATUSES as $status) {
                    $stats['licenses'][$license][$status] = $counts["{$license}_{$status}"];
                }
            }
            $stats['subscriptions'] = self::subscriptionStats($companies);
        }

        return $stats;
    }

    public static function authorizeStatement(string $type): void
    {
        if (isset(self::licenseBuckets()[$type])) {
            Gate::authorize('viewAny', Company::class);

            return;
        }

        abort_unless(isset(self::documentBuckets()[$type]), 404);
        Gate::authorize('viewAny', Employee::class);
    }

    public static function statementTitle(string $type): string
    {
        foreach (array_keys(self::LICENSE_FIELDS) as $license) {
            if (str_starts_with($type, $license.'_')) {
                return __('performance.compliance.license_status', [
                    'license' => __('performance.compliance.'.$license),
                    'status' => __('performance.compliance.'.substr($type, strlen($license) + 1)),
                ]);
            }
        }

        return __('performance.compliance.'.$type);
    }

    public static function getKashfQuery(
        array $filters,
        string $type = 'iqama_expired',
        ?int $companyId = null,
        int|string|null $departmentId = null
    ): Builder {
        self::authorizeStatement($type);
        $companyIds = self::companyIds($filters);
        if ($companyId !== null) {
            $companyIds = array_intersect($companyIds, [$companyId]);
        }

        $licenseBuckets = self::licenseBuckets();
        if (isset($licenseBuckets[$type])) {
            [$field, $condition, $bindings] = $licenseBuckets[$type];

            return self::companyQuery($companyIds)
                ->select('companies.*')
                ->selectRaw("{$field} as compliance_expiry")
                ->whereRaw("{$field} {$condition}", $bindings)
                ->orderBy($field)
                ->orderBy('companies.id');
        }

        [$field, $condition, $bindings] = self::documentBuckets()[$type];
        $query = self::employeeQuery($filters, $companyIds)
            ->with(['user', 'company', 'branch'])
            ->select('employees.*')
            ->selectRaw("{$field} as compliance_expiry")
            ->whereRaw("{$field} {$condition}", $bindings);

        if ($departmentId !== null) {
            $departmentId === 'unassigned' || $departmentId === 0
                ? $query->whereNull('employees.department_id')
                : $query->where('employees.department_id', (int) $departmentId);
        }

        return $query->orderBy($field)->orderBy('employees.id');
    }

    private static function companyIds(array $filters): array
    {
        $ids = CompanyDepartmentPerformanceService::getAuthorizedCompanyIds();

        return filled($filters['company_id'] ?? null) ? array_intersect($ids, (array) $filters['company_id']) : $ids;
    }

    private static function companyQuery(array $companyIds): Builder
    {
        return Company::query()->whereIn('companies.id', $companyIds);
    }

    private static function employeeQuery(array $filters, array $companyIds): Builder
    {
        $user = auth()->user();
        $branchId = CompanyDepartmentPerformanceService::resolveBranchId($filters, $user);
        $teamIds = CompanyDepartmentPerformanceService::isSupervisorViewer($user)
            ? CompanyDepartmentPerformanceService::supervisorTeamIds($user, ...CompanyDepartmentPerformanceService::resolveDateRange($filters))
            : null;

        $canViewSaudi = $user->isSuperAdminOrOwner() || $user->can('view_any_saudi_employee');
        $canViewForeign = $user->isSuperAdminOrOwner() || $user->can('view_any_foreign_employee');

        return Employee::query()
            ->join('employee_details', 'employees.id', '=', 'employee_details.employee_id')
            ->whereIn('employees.company_id', $companyIds)
            ->where('employees.status', 'active')
            ->when($branchId !== null, fn (Builder $query) => $query->where('employees.branch_id', $branchId))
            ->when($teamIds !== null, fn (Builder $query) => $query->whereIn('employees.id', $teamIds))
            ->when(! $canViewSaudi, fn (Builder $query) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('user_type', UserType::FOREIGN_EMPLOYEE->value)))
            ->when(! $canViewForeign, fn (Builder $query) => $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('user_type', '<>', UserType::FOREIGN_EMPLOYEE->value)));
    }

    private static function documentBuckets(): array
    {
        $today = Carbon::today();
        $start = $today->toDateString();
        $after90Days = $today->copy()->addDays(self::DOCUMENT_WARNING_DAYS + 1)->toDateString();
        $after180Days = $today->copy()->addDays(181)->toDateString();
        $after270Days = $today->copy()->addDays(271)->toDateString();
        $iqama = 'employee_details.iqama_expiry';

        return [
            'iqama_expired' => [$iqama, '< ?', [$start]],
            'iqama_within_3m' => [$iqama, ">= ? and {$iqama} < ?", [$start, $after90Days]],
            'iqama_within_6m' => [$iqama, ">= ? and {$iqama} < ?", [$after90Days, $after180Days]],
            'iqama_within_9m' => [$iqama, ">= ? and {$iqama} < ?", [$after180Days, $after270Days]],
            'iqama_valid_over_9m' => [$iqama, '>= ?', [$after270Days]],
            ...self::expiryBuckets('work_permit', 'employee_details.work_permit_expiry', $start, $after90Days),
            ...self::expiryBuckets('insurance', 'employee_details.insurance_expiry', $start, $after90Days),
        ];
    }

    private static function licenseBuckets(): array
    {
        $today = Carbon::today();
        $start = $today->toDateString();
        $after30Days = $today->copy()->addDays(self::LICENSE_WARNING_DAYS + 1)->toDateString();
        $buckets = [];
        foreach (self::LICENSE_FIELDS as $license => $field) {
            $buckets += self::expiryBuckets($license, $field, $start, $after30Days);
            $buckets["{$license}_unspecified"] = [$field, 'is null', []];
        }

        return $buckets;
    }

    private static function expiryBuckets(string $prefix, string $field, string $start, string $end): array
    {
        return [
            "{$prefix}_expired" => [$field, '< ?', [$start]],
            "{$prefix}_expiring_soon" => [$field, ">= ? and {$field} < ?", [$start, $end]],
            "{$prefix}_valid" => [$field, '>= ?', [$end]],
        ];
    }

    private static function countBuckets(Builder $query, array $buckets): array
    {
        $query = (clone $query)->toBase();
        foreach ($buckets as $key => [$field, $condition, $bindings]) {
            $query->selectRaw("COUNT(CASE WHEN {$field} {$condition} THEN 1 END) as {$key}", $bindings);
        }

        return array_map('intval', (array) $query->first());
    }

    private static function subscriptionStats(Builder $companies): array
    {
        $stats = array_fill_keys(['qiwa', 'absher', 'spl'], ['active' => 0, 'expired' => 0, 'unspecified' => 0]);
        $today = Carbon::today();
        foreach ($companies->get(['metadata']) as $company) {
            foreach ($stats as $platform => $counts) {
                $date = data_get($company->metadata, "subscriptions.{$platform}.expiry_date");
                $status = 'unspecified';
                if (is_string($date) && Carbon::canBeCreatedFromFormat($date, 'Y-m-d')) {
                    $status = Carbon::parse($date)->lt($today) ? 'expired' : 'active';
                }
                $stats[$platform][$status]++;
            }
        }

        return $stats;
    }
}
