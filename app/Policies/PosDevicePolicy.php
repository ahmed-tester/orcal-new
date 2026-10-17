<?php

namespace App\Policies;

use App\Models\PosDevice;
use App\Models\User;

class PosDevicePolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_pos_device');
    }

    public function view(User $user, PosDevice $model): bool
    {
        return $user->can('view_any_pos_device');
    }

    public function create(User $user): bool
    {
        return $user->can('create_pos_device');
    }

    public function update(User $user, PosDevice $model): bool
    {
        return $user->can('update_pos_device');
    }

    public function delete(User $user, PosDevice $model): bool
    {
        return $user->can('delete_pos_device');
    }
}
