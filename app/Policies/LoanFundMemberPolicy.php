<?php

namespace App\Policies;

use App\Models\LoanFundMember;
use App\Models\User;

class LoanFundMemberPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_loan_fund_member');
    }

    public function view(User $user, LoanFundMember $model): bool
    {
        return $user->can('view_any_loan_fund_member');
    }

    public function create(User $user): bool
    {
        return $user->can('create_loan_fund_member');
    }

    public function update(User $user, LoanFundMember $model): bool
    {
        return $user->can('update_loan_fund_member');
    }

    public function delete(User $user, LoanFundMember $model): bool
    {
        return $user->can('delete_loan_fund_member');
    }
}
