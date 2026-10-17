<?php

namespace App\Policies;

use App\Models\ForeignEmployee;
use App\Models\User;

class ForeignEmployeePolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_foreign_employee');
    }

    public function view(User $user, ForeignEmployee $model): bool
    {
        return $user->can('view_any_foreign_employee');
    }

    public function create(User $user): bool
    {
        return $user->can('create_foreign_employee');
    }

    public function update(User $user, ForeignEmployee $model): bool
    {
        return $user->can('update_foreign_employee');
    }

    public function delete(User $user, ForeignEmployee $model): bool
    {
        return $user->can('delete_foreign_employee');
    }
}
