<?php

namespace App\Policies;

use App\Models\TemporaryEmployee;
use App\Models\User;

class TemporaryEmployeePolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_temporary_employee')
            || $user->can('view_any_employee')
            || $user->can('view_any_saudi_employee');
    }

    public function view(User $user, TemporaryEmployee $model): bool
    {
        return $user->can('view_any_temporary_employee')
            || $user->can('view_any_employee')
            || $user->can('view_any_saudi_employee');
    }

    public function create(User $user): bool
    {
        return $user->can('create_temporary_employee')
            || $user->can('create_employee')
            || $user->can('create_saudi_employee');
    }

    public function update(User $user, TemporaryEmployee $model): bool
    {
        return $user->can('update_temporary_employee')
            || $user->can('update_employee')
            || $user->can('update_saudi_employee');
    }

    public function delete(User $user, TemporaryEmployee $model): bool
    {
        return $user->can('delete_temporary_employee')
            || $user->can('delete_employee')
            || $user->can('delete_saudi_employee');
    }
}

