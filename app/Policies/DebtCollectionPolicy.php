<?php

namespace App\Policies;

use App\Models\DebtCollection;
use App\Models\User;

class DebtCollectionPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_debt_collection');
    }

    public function view(User $user, DebtCollection $model): bool
    {
        return $user->can('view_any_debt_collection');
    }

    public function create(User $user): bool
    {
        return $user->can('create_debt_collection');
    }

    public function update(User $user, DebtCollection $model): bool
    {
        return $user->can('update_debt_collection');
    }

    public function delete(User $user, DebtCollection $model): bool
    {
        return $user->can('delete_debt_collection');
    }
}
