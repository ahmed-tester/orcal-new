<?php

namespace App\Policies\Mail;

use App\Models\Mail\MailAccount;
use App\Models\User;
use App\Services\Mail\MailAccessService;

class MailAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return MailAccessService::canAdministerAllMail($user)
            || $user->can(MailAccessService::PERM_MANAGE_ACCOUNTS);
    }

    public function view(User $user, MailAccount $account): bool
    {
        if (MailAccessService::canAdministerAllMail($user)) {
            return true;
        }

        return $user->can(MailAccessService::PERM_MANAGE_ACCOUNTS)
            && MailAccessService::hasCompanyAccess($user, (int) $account->company_id);
    }

    public function create(User $user): bool
    {
        return MailAccessService::canAdministerAllMail($user)
            || $user->can(MailAccessService::PERM_MANAGE_ACCOUNTS);
    }

    public function update(User $user, MailAccount $account): bool
    {
        return $this->view($user, $account);
    }

    public function delete(User $user, MailAccount $account): bool
    {
        return $this->view($user, $account);
    }

    public function deleteAny(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function sync(User $user, MailAccount $account): bool
    {
        if (MailAccessService::canAdministerAllMail($user)) {
            return true;
        }

        return $user->can(MailAccessService::PERM_RUN_SYNC)
            && ($this->view($user, $account) || MailAccessService::canViewAccount($user, $account));
    }
}
