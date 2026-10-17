<?php

namespace App\Policies;

use App\Models\SaudiEmployee;
use App\Models\User;

class SaudiEmployeePolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_saudi_employee');
    }

    public function view(User $user, SaudiEmployee $model): bool
    {
        return $user->can('view_any_saudi_employee');
    }

    public function create(User $user): bool
    {
        return $user->can('create_saudi_employee');
    }

    public function update(User $user, SaudiEmployee $model): bool
    {
        return $user->can('update_saudi_employee');
    }

    public function delete(User $user, SaudiEmployee $model): bool
    {
        return $user->can('delete_saudi_employee');
    }
}
