<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Violation;

class ViolationPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_violation');
    }

    public function view(User $user, Violation $model): bool
    {
        return $user->can('view_any_violation');
    }

    public function create(User $user): bool
    {
        return $user->can('create_violation');
    }

    public function update(User $user, Violation $model): bool
    {
        return $user->can('update_violation');
    }

    public function delete(User $user, Violation $model): bool
    {
        return $user->can('delete_violation');
    }
}
