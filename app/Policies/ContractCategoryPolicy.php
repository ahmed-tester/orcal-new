<?php

namespace App\Policies;

use App\Models\ContractCategory;
use App\Models\User;

class ContractCategoryPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('manage_contract_categories');
    }

    public function view(User $user, ContractCategory $model): bool
    {
        return $user->can('manage_contract_categories');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_contract_categories');
    }

    public function update(User $user, ContractCategory $model): bool
    {
        return $user->can('manage_contract_categories');
    }

    public function delete(User $user, ContractCategory $model): bool
    {
        return $user->can('manage_contract_categories');
    }
}
