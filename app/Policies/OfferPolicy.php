<?php

namespace App\Policies;

use App\Models\Offer;
use App\Models\User;

class OfferPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_offer');
    }

    public function view(User $user, Offer $model): bool
    {
        return $user->can('view_any_offer');
    }

    public function create(User $user): bool
    {
        return $user->can('create_offer');
    }

    public function update(User $user, Offer $model): bool
    {
        return $user->can('update_offer');
    }

    public function delete(User $user, Offer $model): bool
    {
        return $user->can('delete_offer');
    }
}
