<?php

namespace App\Policies;

use App\Models\AttendanceDevice;
use App\Models\User;

class AttendanceDevicePolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_attendance_device');
    }

    public function view(User $user, AttendanceDevice $model): bool
    {
        return $user->can('view_any_attendance_device');
    }

    public function create(User $user): bool
    {
        return $user->can('create_attendance_device');
    }

    public function update(User $user, AttendanceDevice $model): bool
    {
        return $user->can('update_attendance_device');
    }

    public function delete(User $user, AttendanceDevice $model): bool
    {
        return $user->can('delete_attendance_device');
    }
}
