<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ViolationRegulation;

class ViolationRegulationPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_violation_regulation');
    }

    public function view(User $user, ViolationRegulation $model): bool
    {
        return $user->can('view_any_violation_regulation');
    }

    public function create(User $user): bool
    {
        return $user->can('create_violation_regulation');
    }

    public function update(User $user, ViolationRegulation $model): bool
    {
        return $user->can('update_violation_regulation');
    }

    public function delete(User $user, ViolationRegulation $model): bool
    {
        return $user->can('delete_violation_regulation');
    }
}
