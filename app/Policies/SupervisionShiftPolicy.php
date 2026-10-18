<?php

namespace App\Policies;

use App\Models\SupervisionShift;
use App\Models\User;

class SupervisionShiftPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_supervision_shift');
    }

    public function view(User $user, SupervisionShift $model): bool
    {
        return $user->can('view_any_supervision_shift');
    }

    public function create(User $user): bool
    {
        return $user->can('create_supervision_shift');
    }

    public function update(User $user, SupervisionShift $model): bool
    {
        return $user->can('update_supervision_shift');
    }

    public function delete(User $user, SupervisionShift $model): bool
    {
        return $user->can('delete_supervision_shift');
    }
}
