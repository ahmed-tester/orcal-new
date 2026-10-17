<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WeeklyNote;

class WeeklyNotePolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_weekly_note');
    }

    public function view(User $user, WeeklyNote $model): bool
    {
        return $user->can('view_any_weekly_note');
    }

    public function create(User $user): bool
    {
        return $user->can('create_weekly_note');
    }

    public function update(User $user, WeeklyNote $model): bool
    {
        return $user->can('update_weekly_note');
    }

    public function delete(User $user, WeeklyNote $model): bool
    {
        return $user->can('delete_weekly_note');
    }
}
