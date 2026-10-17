<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_branch');
    }

    public function view(User $user, Branch $model): bool
    {
        return $user->can('view_any_branch');
    }

    public function create(User $user): bool
    {
        return $user->can('create_branch');
    }

    public function update(User $user, Branch $model): bool
    {
        return $user->can('update_branch');
    }

    public function delete(User $user, Branch $model): bool
    {
        return $user->can('delete_branch');
    }
}
