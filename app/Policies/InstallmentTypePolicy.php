<?php

namespace App\Policies;

use App\Models\InstallmentType;
use App\Models\User;

class InstallmentTypePolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_installment_type');
    }

    public function view(User $user, InstallmentType $model): bool
    {
        return $user->can('view_any_installment_type');
    }

    public function create(User $user): bool
    {
        return $user->can('create_installment_type');
    }

    public function update(User $user, InstallmentType $model): bool
    {
        return $user->can('update_installment_type');
    }

    public function delete(User $user, InstallmentType $model): bool
    {
        return $user->can('delete_installment_type');
    }
}
