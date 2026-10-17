<?php

namespace App\Policies;

use App\Models\Faq;
use App\Models\User;

class FaqPolicy
{
    use HasSuperAdminBypass;

    /**
     * Can the user browse the FAQ list page?
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_faq');
    }

    /**
     * Can the user view a single FAQ record?
     */
    public function view(User $user, Faq $faq): bool
    {
        return $user->can('view_any_faq');
    }

    /**
     * Can the user create a new FAQ?
     */
    public function create(User $user): bool
    {
        return $user->can('create_faq');
    }

    /**
     * Can the user edit an existing FAQ?
     */
    public function update(User $user, Faq $faq): bool
    {
        return $user->can('update_faq');
    }

    /**
     * Can the user delete an FAQ?
     */
    public function delete(User $user, Faq $faq): bool
    {
        return $user->can('delete_faq');
    }

    /**
     * Can the user delete multiple FAQs at once?
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_faq');
    }
}
