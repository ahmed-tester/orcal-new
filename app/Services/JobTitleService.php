<?php

namespace App\Services;

use App\Enums\JobTitleScope;
use App\Models\JobTitle;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class JobTitleService
{
    /**
     * Prepare and sanitize job title form data before create or update.
     */
    public function prepareFormData(array $data, array $rawState = []): array
    {
        $scope = $rawState['title_scope'] ?? ($data['title_scope'] ?? JobTitleScope::COMPANY->value);

        if (JobTitleScope::isGlobalValue($scope) || ! empty($data['is_global'])) {
            $data['is_global'] = true;
            $data['company_id'] = null;
            $data['department_id'] = null;
        } else {
            $data['is_global'] = false;
        }

        unset($data['title_scope']);

        return $data;
    }

    /**
     * Retrieve available job title options for a given company (company-specific + global group-wide titles).
     */
    public function getAvailableForCompanyOptions(?int $companyId = null, ?User $user = null): Collection
    {
        $query = JobTitle::query()->where('status', 'active');

        if ($companyId) {
            $query->where(function (Builder $q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->orWhere('is_global', true)
                    ->orWhereNull('company_id');
            });
        } elseif ($user && ! $user->isSuperAdmin()) {
            $companyIds = CompanyFormService::getAuthorizedCompanyIds($user);
            $query->where(function (Builder $q) use ($companyIds) {
                $q->whereIn('company_id', ! empty($companyIds) ? $companyIds : [0])
                    ->orWhere('is_global', true)
                    ->orWhereNull('company_id');
            });
        }

        return $query->with('company')
            ->orderBy('is_global', 'desc')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (JobTitle $jt) use ($companyId) {
                return [$jt->id => $jt->formatted_name_with_scope];
            });
    }

    /**
     * Retrieve available parent job title options.
     */
    public function getParentTitleOptions(?int $companyId, ?int $ignoreId = null): Collection
    {
        $query = JobTitle::query()->where('status', 'active');

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($companyId) {
            $query->availableForCompany($companyId);
        }

        return $query->orderBy('sort_order')->orderBy('name')->pluck('name', 'id');
    }
}

