<?php

namespace App\Policies;

use App\Models\LoanFundLoan;
use App\Models\User;

class LoanFundLoanPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_loan_fund_loan');
    }

    public function view(User $user, LoanFundLoan $model): bool
    {
        return $user->can('view_any_loan_fund_loan');
    }

    public function create(User $user): bool
    {
        return $user->can('create_loan_fund_loan');
    }

    public function update(User $user, LoanFundLoan $model): bool
    {
        return $user->can('update_loan_fund_loan');
    }

    public function delete(User $user, LoanFundLoan $model): bool
    {
        return $user->can('delete_loan_fund_loan');
    }
}
