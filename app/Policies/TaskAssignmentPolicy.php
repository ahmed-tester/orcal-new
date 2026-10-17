<?php

namespace App\Policies;

use App\Models\TaskAssignment;
use App\Models\User;

class TaskAssignmentPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_task_assignment');
    }

    public function view(User $user, TaskAssignment $model): bool
    {
        return $user->can('view_any_task_assignment');
    }

    public function create(User $user): bool
    {
        return $user->can('create_task_assignment');
    }

    public function update(User $user, TaskAssignment $model): bool
    {
        return $user->can('update_task_assignment');
    }

    public function delete(User $user, TaskAssignment $model): bool
    {
        return $user->can('delete_task_assignment');
    }
}
