<?php

namespace App\Policies\Mail;

use App\Models\User;
use App\Services\Mail\MailAccessService;
use Illuminate\Database\Eloquent\Model;

class MailRulePolicy
{
    public function viewAny(User $user): bool
    {
        return MailAccessService::canAdministerAllMail($user)
            || $user->can(MailAccessService::PERM_MANAGE_RULES);
    }

    public function view(User $user, Model $rule): bool
    {
        if (MailAccessService::canAdministerAllMail($user)) {
            return true;
        }

        $companyId = isset($rule->company_id) && $rule->company_id !== null
            ? (int) $rule->company_id
            : null;

        return $user->can(MailAccessService::PERM_MANAGE_RULES)
            && MailAccessService::hasCompanyAccess($user, $companyId);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Model $rule): bool
    {
        return $this->view($user, $rule);
    }

    public function delete(User $user, Model $rule): bool
    {
        return $this->view($user, $rule);
    }
}
