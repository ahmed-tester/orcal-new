<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\User;

trait HasSuperAdminBypass
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdminOrOwner()) {
            return true;
        }

        return null;
    }
}
