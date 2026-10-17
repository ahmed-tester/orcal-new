<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_department');
    }

    public function view(User $user, Department $model): bool
    {
        return $user->can('view_any_department');
    }

    public function create(User $user): bool
    {
        return $user->can('create_department');
    }

    public function update(User $user, Department $model): bool
    {
        return $user->can('update_department');
    }

    public function delete(User $user, Department $model): bool
    {
        return $user->can('delete_department');
    }
}
