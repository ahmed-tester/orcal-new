<?php

namespace App\Policies\Mail;

use App\Models\Mail\MailMessage;
use App\Models\User;
use App\Services\Mail\MailAccessService;

class MailMessagePolicy
{
    public function viewAny(User $user): bool
    {
        return MailAccessService::canAdministerAllMail($user)
            || $user->can(MailAccessService::PERM_VIEW_INBOX);
    }

    public function view(User $user, MailMessage $message): bool
    {
        return MailAccessService::canViewMessage($user, $message);
    }

    public function reply(User $user, MailMessage $message): bool
    {
        if (MailAccessService::canAdministerAllMail($user)) {
            return true;
        }

        return $user->can(MailAccessService::PERM_REPLY)
            && MailAccessService::canViewMessage($user, $message);
    }

    public function review(User $user, MailMessage $message): bool
    {
        if (MailAccessService::canAdministerAllMail($user)) {
            return true;
        }

        return $user->can(MailAccessService::PERM_REVIEW)
            && MailAccessService::canViewMessage($user, $message);
    }

    public function delete(User $user, MailMessage $message): bool
    {
        if (MailAccessService::canAdministerAllMail($user)) {
            return true;
        }

        return $user->can(MailAccessService::PERM_DELETE)
            && MailAccessService::canViewMessage($user, $message);
    }

    public function deleteAny(User $user): bool
    {
        return MailAccessService::canAdministerAllMail($user)
            || $user->can(MailAccessService::PERM_DELETE);
    }
}
