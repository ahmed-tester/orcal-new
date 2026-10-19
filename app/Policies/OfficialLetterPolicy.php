<?php

namespace App\Policies;

use App\Models\OfficialLetter;
use App\Models\User;

class OfficialLetterPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_official_letter');
    }

    public function view(User $user, OfficialLetter $model): bool
    {
        return $user->can('view_any_official_letter');
    }

    public function create(User $user): bool
    {
        return $user->can('create_official_letter');
    }

    public function update(User $user, OfficialLetter $model): bool
    {
        return $user->can('update_official_letter');
    }

    public function delete(User $user, OfficialLetter $model): bool
    {
        return $user->can('delete_official_letter');
    }
}
