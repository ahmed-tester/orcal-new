<?php

namespace App\Policies;

use App\Models\JobTitle;
use App\Models\User;

class JobTitlePolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_job_title');
    }

    public function view(User $user, JobTitle $model): bool
    {
        return $user->can('view_any_job_title');
    }

    public function create(User $user): bool
    {
        return $user->can('create_job_title');
    }

    public function update(User $user, JobTitle $model): bool
    {
        return $user->can('update_job_title');
    }

    public function delete(User $user, JobTitle $model): bool
    {
        return $user->can('delete_job_title');
    }
}
