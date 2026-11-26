<?php

namespace App\Policies;

use App\Models\Directives\Directive;
use App\Models\User;

class DirectivePolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_directive');
    }

    public function view(User $user, Directive $directive): bool
    {
        return $user->can('view_any_directive');
    }

    public function create(User $user): bool
    {
        return $user->can('create_directive');
    }

    public function update(User $user, Directive $directive): bool
    {
        return $user->can('update_directive');
    }

    public function delete(User $user, Directive $directive): bool
    {
        return $user->can('delete_directive');
    }
}
