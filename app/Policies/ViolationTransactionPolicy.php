<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ViolationTransaction;

class ViolationTransactionPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_violation_transaction');
    }

    public function view(User $user, ViolationTransaction $model): bool
    {
        return $user->can('view_any_violation_transaction');
    }

    public function create(User $user): bool
    {
        return $user->can('create_violation_transaction');
    }

    public function update(User $user, ViolationTransaction $model): bool
    {
        return $user->can('update_violation_transaction');
    }

    public function delete(User $user, ViolationTransaction $model): bool
    {
        return $user->can('delete_violation_transaction');
    }
}
