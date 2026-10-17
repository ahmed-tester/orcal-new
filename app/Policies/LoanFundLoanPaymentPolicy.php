<?php

namespace App\Policies;

use App\Models\LoanFundLoanPayment;
use App\Models\User;

class LoanFundLoanPaymentPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_loan_fund_loan_payment');
    }

    public function view(User $user, LoanFundLoanPayment $model): bool
    {
        return $user->can('view_any_loan_fund_loan_payment');
    }

    public function create(User $user): bool
    {
        return $user->can('create_loan_fund_loan_payment');
    }

    public function update(User $user, LoanFundLoanPayment $model): bool
    {
        return $user->can('update_loan_fund_loan_payment');
    }

    public function delete(User $user, LoanFundLoanPayment $model): bool
    {
        return $user->can('delete_loan_fund_loan_payment');
    }
}
