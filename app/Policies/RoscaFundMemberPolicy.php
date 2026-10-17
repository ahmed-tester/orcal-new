<?php

namespace App\Policies;

use App\Models\RoscaFundMember;
use App\Models\User;

class RoscaFundMemberPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_rosca_fund_member');
    }

    public function view(User $user, RoscaFundMember $model): bool
    {
        return $user->can('view_any_rosca_fund_member');
    }

    public function create(User $user): bool
    {
        return $user->can('create_rosca_fund_member');
    }

    public function update(User $user, RoscaFundMember $model): bool
    {
        return $user->can('update_rosca_fund_member');
    }

    public function delete(User $user, RoscaFundMember $model): bool
    {
        return $user->can('delete_rosca_fund_member');
    }
}
