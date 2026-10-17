<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ViolationCategory;

class ViolationCategoryPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_violation_category');
    }

    public function view(User $user, ViolationCategory $model): bool
    {
        return $user->can('view_any_violation_category');
    }

    public function create(User $user): bool
    {
        return $user->can('create_violation_category');
    }

    public function update(User $user, ViolationCategory $model): bool
    {
        return $user->can('update_violation_category');
    }

    public function delete(User $user, ViolationCategory $model): bool
    {
        return $user->can('delete_violation_category');
    }
}
