<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ViolationType;

class ViolationTypePolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_violation_type');
    }

    public function view(User $user, ViolationType $model): bool
    {
        return $user->can('view_any_violation_type');
    }

    public function create(User $user): bool
    {
        return $user->can('create_violation_type');
    }

    public function update(User $user, ViolationType $model): bool
    {
        return $user->can('update_violation_type');
    }

    public function delete(User $user, ViolationType $model): bool
    {
        return $user->can('delete_violation_type');
    }
}
