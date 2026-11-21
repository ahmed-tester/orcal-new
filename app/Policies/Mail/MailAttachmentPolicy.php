<?php

namespace App\Policies\Mail;

use App\Models\Mail\MailAttachment;
use App\Models\User;
use App\Services\Mail\MailAccessService;

class MailAttachmentPolicy
{
    public function view(User $user, MailAttachment $attachment): bool
    {
        return MailAccessService::canViewAttachment($user, $attachment);
    }
}
