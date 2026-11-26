<?php

namespace App\Policies;

use App\Models\DirectiveCategory;
use App\Models\User;

class DirectiveCategoryPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_directive_category');
    }

    public function view(User $user, DirectiveCategory $category): bool
    {
        return $user->can('view_any_directive_category');
    }

    public function create(User $user): bool
    {
        return $user->can('create_directive_category');
    }

    public function update(User $user, DirectiveCategory $category): bool
    {
        return $user->can('update_directive_category');
    }

    public function delete(User $user, DirectiveCategory $category): bool
    {
        return $user->can('delete_directive_category')
            && $category->directives()->doesntExist();
    }
}
