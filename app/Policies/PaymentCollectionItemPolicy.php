<?php

namespace App\Policies;

use App\Models\PaymentCollectionItem;
use App\Models\User;

class PaymentCollectionItemPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_payment_collection_item');
    }

    public function view(User $user, PaymentCollectionItem $model): bool
    {
        return $user->can('view_any_payment_collection_item');
    }

    public function create(User $user): bool
    {
        return $user->can('create_payment_collection_item');
    }

    public function update(User $user, PaymentCollectionItem $model): bool
    {
        return $user->can('update_payment_collection_item');
    }

    public function delete(User $user, PaymentCollectionItem $model): bool
    {
        return $user->can('delete_payment_collection_item');
    }
}
