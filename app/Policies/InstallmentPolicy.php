<?php

namespace App\Policies;

use App\Models\Installment;
use App\Models\User;

class InstallmentPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_installment');
    }

    public function view(User $user, Installment $model): bool
    {
        return $user->can('view_any_installment');
    }

    public function create(User $user): bool
    {
        return $user->can('create_installment');
    }

    public function update(User $user, Installment $model): bool
    {
        return $user->can('update_installment');
    }

    public function delete(User $user, Installment $model): bool
    {
        return $user->can('delete_installment');
    }
}
