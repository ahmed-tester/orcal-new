<?php

namespace App\Policies;

use App\Models\Debt;
use App\Models\User;

class DebtPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_debt');
    }

    public function view(User $user, Debt $model): bool
    {
        return $user->can('view_any_debt');
    }

    public function create(User $user): bool
    {
        return $user->can('create_debt');
    }

    public function update(User $user, Debt $model): bool
    {
        return $user->can('update_debt');
    }

    public function delete(User $user, Debt $model): bool
    {
        return $user->can('delete_debt');
    }
}
