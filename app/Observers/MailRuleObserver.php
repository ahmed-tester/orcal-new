<?php

namespace App\Observers;

use App\Models\User;
use App\Services\Mail\MailAccessService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class MailRuleObserver
{
    public function saving(Model $rule): void
    {
        $user = auth()->user();
        if (! $user instanceof User) {
            return;
        }

        Gate::authorize($rule->exists ? 'update' : 'create', $rule->exists ? $rule : $rule::class);
        abort_unless(MailAccessService::hasCompanyAccess($user, $rule->company_id), 403);
    }
}
