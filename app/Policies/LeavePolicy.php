<?php

namespace App\Policies;

use App\Models\Leave;
use App\Models\User;

class LeavePolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_leave');
    }

    public function view(User $user, Leave $model): bool
    {
        return $user->can('view_any_leave');
    }

    public function create(User $user): bool
    {
        return $user->can('create_leave');
    }

    public function update(User $user, Leave $model): bool
    {
        return $user->can('update_leave');
    }

    public function delete(User $user, Leave $model): bool
    {
        return $user->can('delete_leave');
    }
}
