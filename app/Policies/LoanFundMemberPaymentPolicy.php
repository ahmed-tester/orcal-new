<?php

namespace App\Policies;

use App\Models\LoanFundMemberPayment;
use App\Models\User;

class LoanFundMemberPaymentPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_loan_fund_member_payment') || $user->can('view_any_loan_fund_member');
    }

    public function view(User $user, LoanFundMemberPayment $model): bool
    {
        return $user->can('view_any_loan_fund_member_payment') || $user->can('view_any_loan_fund_member');
    }

    public function create(User $user): bool
    {
        return $user->can('create_loan_fund_member_payment') || $user->can('create_loan_fund_member');
    }

    public function update(User $user, LoanFundMemberPayment $model): bool
    {
        return $user->can('update_loan_fund_member_payment') || $user->can('update_loan_fund_member');
    }

    public function delete(User $user, LoanFundMemberPayment $model): bool
    {
        return $user->can('delete_loan_fund_member_payment') || $user->can('delete_loan_fund_member');
    }
}
