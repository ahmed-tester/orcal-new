<?php

namespace App\Policies;

use App\Models\EmployeeReward;
use App\Models\User;

class EmployeeRewardPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_employee_reward');
    }

    public function view(User $user, EmployeeReward $model): bool
    {
        return $user->can('view_any_employee_reward');
    }

    public function create(User $user): bool
    {
        return $user->can('create_employee_reward');
    }

    public function update(User $user, EmployeeReward $model): bool
    {
        return $user->can('update_employee_reward');
    }

    public function delete(User $user, EmployeeReward $model): bool
    {
        return $user->can('delete_employee_reward');
    }
}
