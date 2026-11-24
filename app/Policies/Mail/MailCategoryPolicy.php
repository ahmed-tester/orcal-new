<?php

namespace App\Policies\Mail;

use App\Models\Mail\MailCategory;
use App\Models\User;
use App\Services\Mail\MailAccessService;

class MailCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return MailAccessService::canAdministerAllMail($user);
    }

    public function view(User $user, MailCategory $category): bool
    {
        return MailAccessService::canAdministerAllMail($user);
    }

    public function create(User $user): bool
    {
        return MailAccessService::canAdministerAllMail($user);
    }

    public function update(User $user, MailCategory $category): bool
    {
        return MailAccessService::canAdministerAllMail($user);
    }

    public function delete(User $user, MailCategory $category): bool
    {
        return MailAccessService::canAdministerAllMail($user)
            && ! $category->is_system
            && $category->slug !== MailCategory::SYSTEM_OTHER_SLUG
            && $category->messages()->doesntExist();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
