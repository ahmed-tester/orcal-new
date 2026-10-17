<?php

namespace App\Policies;

use App\Models\DailyJournal;
use App\Models\User;

class DailyJournalPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_daily_journal');
    }

    public function view(User $user, DailyJournal $model): bool
    {
        return $user->can('view_any_daily_journal');
    }

    public function create(User $user): bool
    {
        return $user->can('create_daily_journal');
    }

    public function update(User $user, DailyJournal $model): bool
    {
        return $user->can('update_daily_journal');
    }

    public function delete(User $user, DailyJournal $model): bool
    {
        return $user->can('delete_daily_journal');
    }
}
