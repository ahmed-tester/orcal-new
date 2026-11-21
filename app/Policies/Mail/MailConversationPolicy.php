<?php

namespace App\Policies\Mail;

use App\Models\Mail\MailConversation;
use App\Models\User;
use App\Services\Mail\MailAccessService;

class MailConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return MailAccessService::canAdministerAllMail($user)
            || $user->can(MailAccessService::PERM_VIEW_INBOX);
    }

    public function view(User $user, MailConversation $conversation): bool
    {
        return MailAccessService::canViewConversation($user, $conversation);
    }
}
