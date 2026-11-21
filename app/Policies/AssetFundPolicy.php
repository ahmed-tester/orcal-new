<?php

namespace App\Policies;

use App\Models\AssetFund;
use App\Models\User;

class AssetFundPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_asset_fund');
    }

    public function view(User $user, AssetFund $model): bool
    {
        return $user->can('view_any_asset_fund') && $model->isAccessibleBy($user);
    }

    public function create(User $user): bool
    {
        return $user->can('create_asset_fund');
    }

    public function update(User $user, AssetFund $model): bool
    {
        return $user->can('update_asset_fund') && $model->isAccessibleBy($user);
    }

    public function delete(User $user, AssetFund $model): bool
    {
        return $user->can('delete_asset_fund') && $model->isAccessibleBy($user);
    }
}

