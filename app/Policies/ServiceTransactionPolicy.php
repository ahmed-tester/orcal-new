<?php

namespace App\Policies;

use App\Models\ServiceTransaction;
use App\Models\User;

class ServiceTransactionPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_service_transaction');
    }

    public function view(User $user, ServiceTransaction $model): bool
    {
        return $user->can('view_any_service_transaction');
    }

    public function create(User $user): bool
    {
        return $user->can('create_service_transaction');
    }

    public function update(User $user, ServiceTransaction $model): bool
    {
        return $user->can('update_service_transaction');
    }

    public function delete(User $user, ServiceTransaction $model): bool
    {
        return $user->can('delete_service_transaction');
    }
}
