<?php

namespace App\Filament\Traits;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

trait HasCompanyScope
{
    protected static array $columnsCache = [];

    /**
     * Scope the resource query based on user role and company/branch permissions.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // 1. Super Admin: full access without restrictions
        if (!$user || $user->isSuperAdmin()) {
            return $query;
        }

        $modelClass = static::getModel();
        $instance = new $modelClass;
        $tableName = $instance->getTable();

        // 2. Handling the Company resource directly
        if ($modelClass === Company::class) {
            return static::scopeCompanyModel($query, $user, $tableName);
        }

        // 3. Company Owner: scope strictly by owned company IDs
        if ($user->isOwner()) {
            return static::scopeForOwner($query, $user, $instance, $tableName);
        }

        // 4. Branch Staff: scope by assigned branch only if restricted
        if ($user->branch_id && ! $user->canAccessAllBranches()) {
            return static::scopeForBranchUser($query, $user, $instance, $tableName, $modelClass);
        }

        // 5. Staff with general company assignment (including managers and staff with multi-company access)
        $accessibleIds = $user->getAccessibleCompanyIds();
        if (!empty($accessibleIds)) {
            return static::scopeForCompanyStaff($query, $user, $tableName);
        }

        return $query;
    }

    /**
     * Scope query specifically for the Company model.
     */
    protected static function scopeCompanyModel(Builder $query, User $user, string $tableName): Builder
    {
        if ($user->isOwner()) {
            return $query->whereIn("{$tableName}.id", $user->getOwnedCompanyIds());
        }

        $accessibleIds = $user->getAccessibleCompanyIds();
        if (!empty($accessibleIds)) {
            return $query->whereIn("{$tableName}.id", $accessibleIds);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Scope records for Company Owners.
     */
    protected static function scopeForOwner(Builder $query, User $user, Model $instance, string $tableName): Builder
    {
        $ownedCompanyIds = $user->getOwnedCompanyIds();

        if (static::modelHasColumn($tableName, 'company_id')) {
            if ($tableName === 'departments') {
                return $query->where(function ($q) use ($tableName, $ownedCompanyIds) {
                    $q->whereIn("{$tableName}.company_id", $ownedCompanyIds)
                      ->orWhereHas('companies', fn ($sub) => $sub->whereIn('companies.id', $ownedCompanyIds))
                      ->orWhere("{$tableName}.is_global", true);
                });
            }
            return $query->whereIn("{$tableName}.company_id", $ownedCompanyIds);
        }

        if (method_exists($instance, 'employee')) {
            return $query->whereHas('employee', fn ($q) => $q->whereIn('company_id', $ownedCompanyIds));
        }

        return $query;
    }

    /**
     * Scope records for users assigned to a specific branch.
     */
    protected static function scopeForBranchUser(Builder $query, User $user, Model $instance, string $tableName, string $modelClass): Builder
    {
        if ($modelClass === Branch::class) {
            return $query->where("{$tableName}.id", $user->branch_id);
        }

        if (static::modelHasColumn($tableName, 'branch_id')) {
            return $query->where("{$tableName}.branch_id", $user->branch_id);
        }

        if (method_exists($instance, 'employee')) {
            return $query->whereHas('employee', fn ($q) => $q->where('branch_id', $user->branch_id));
        }

        return $query;
    }

    /**
     * Scope records for general staff by company IDs.
     */
    protected static function scopeForCompanyStaff(Builder $query, User $user, string $tableName): Builder
    {
        $allowedIds = $user->getAccessibleCompanyIds();

        if (static::modelHasColumn($tableName, 'company_id')) {
            if ($tableName === 'departments') {
                return $query->where(function ($q) use ($tableName, $allowedIds) {
                    $q->whereIn("{$tableName}.company_id", $allowedIds)
                      ->orWhereHas('companies', fn ($sub) => $sub->whereIn('companies.id', $allowedIds))
                      ->orWhere("{$tableName}.is_global", true);
                });
            }
            return $query->whereIn("{$tableName}.company_id", $allowedIds);
        }

        return $query;
    }

    /**
     * Check if a table has a specific column (with in-memory caching).
     */
    protected static function modelHasColumn(string $table, string $column): bool
    {
        $cacheKey = "{$table}_{$column}";

        if (!isset(static::$columnsCache[$cacheKey])) {
            static::$columnsCache[$cacheKey] = Schema::hasColumn($table, $column);
        }

        return static::$columnsCache[$cacheKey];
    }
}
