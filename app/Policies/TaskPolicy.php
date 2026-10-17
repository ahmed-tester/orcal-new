<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_task');
    }

    public function view(User $user, Task $model): bool
    {
        return $user->can('view_any_task');
    }

    public function create(User $user): bool
    {
        return $user->can('create_task');
    }

    public function update(User $user, Task $model): bool
    {
        return $user->can('update_task');
    }

    public function delete(User $user, Task $model): bool
    {
        return $user->can('delete_task');
    }
}
