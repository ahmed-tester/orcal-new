<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_saudi_employee') || $user->can('view_any_foreign_employee');
    }

    public function view(User $user, Employee $model): bool
    {
        if ($model->user?->user_type === UserType::FOREIGN_EMPLOYEE) {
            return $user->can('view_any_foreign_employee');
        }

        return $user->can('view_any_saudi_employee');
    }

    public function create(User $user): bool
    {
        return $user->can('create_saudi_employee') || $user->can('create_foreign_employee');
    }

    public function update(User $user, Employee $model): bool
    {
        if ($model->user?->user_type === UserType::FOREIGN_EMPLOYEE) {
            return $user->can('update_foreign_employee');
        }

        return $user->can('update_saudi_employee');
    }

    public function delete(User $user, Employee $model): bool
    {
        if ($model->user?->user_type === UserType::FOREIGN_EMPLOYEE) {
            return $user->can('delete_foreign_employee');
        }

        return $user->can('delete_saudi_employee');
    }
}
