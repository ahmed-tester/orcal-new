<?php

namespace App\Policies;

use App\Models\Owner;
use App\Models\User;

class OwnerPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_owner');
    }

    public function view(User $user, Owner $model): bool
    {
        return $user->can('view_any_owner');
    }

    public function create(User $user): bool
    {
        return $user->can('create_owner');
    }

    public function update(User $user, Owner $model): bool
    {
        return $user->can('update_owner');
    }

    public function delete(User $user, Owner $model): bool
    {
        return $user->can('delete_owner');
    }
}
