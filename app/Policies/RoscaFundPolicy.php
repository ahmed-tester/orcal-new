<?php

namespace App\Policies;

use App\Models\RoscaFund;
use App\Models\User;

class RoscaFundPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_rosca_fund');
    }

    public function view(User $user, RoscaFund $model): bool
    {
        return $user->can('view_any_rosca_fund');
    }

    public function create(User $user): bool
    {
        return $user->can('create_rosca_fund');
    }

    public function update(User $user, RoscaFund $model): bool
    {
        return $user->can('update_rosca_fund');
    }

    public function delete(User $user, RoscaFund $model): bool
    {
        return $user->can('delete_rosca_fund');
    }
}
