<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_customer');
    }

    public function view(User $user, Customer $model): bool
    {
        return $user->can('view_any_customer');
    }

    public function create(User $user): bool
    {
        return $user->can('create_customer');
    }

    public function update(User $user, Customer $model): bool
    {
        return $user->can('update_customer');
    }

    public function delete(User $user, Customer $model): bool
    {
        return $user->can('delete_customer');
    }
}
