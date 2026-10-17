<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_attendance');
    }

    public function view(User $user, Attendance $model): bool
    {
        return $user->can('view_any_attendance');
    }

    public function create(User $user): bool
    {
        return $user->can('create_attendance');
    }

    public function update(User $user, Attendance $model): bool
    {
        return $user->can('update_attendance');
    }

    public function delete(User $user, Attendance $model): bool
    {
        return $user->can('delete_attendance');
    }
}
