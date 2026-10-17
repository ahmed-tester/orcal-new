<?php

namespace App\Policies;

use App\Models\LoanFund;
use App\Models\User;

class LoanFundPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_loan_fund');
    }

    public function view(User $user, LoanFund $model): bool
    {
        return $user->can('view_any_loan_fund');
    }

    public function create(User $user): bool
    {
        return $user->can('create_loan_fund');
    }

    public function update(User $user, LoanFund $model): bool
    {
        return $user->can('update_loan_fund');
    }

    public function delete(User $user, LoanFund $model): bool
    {
        return $user->can('delete_loan_fund');
    }
}
