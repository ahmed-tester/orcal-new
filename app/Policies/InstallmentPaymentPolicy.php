<?php

namespace App\Policies;

use App\Models\InstallmentPayment;
use App\Models\User;

class InstallmentPaymentPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_installment_payment');
    }

    public function view(User $user, InstallmentPayment $model): bool
    {
        return $user->can('view_any_installment_payment');
    }

    public function create(User $user): bool
    {
        return $user->can('create_installment_payment');
    }

    public function update(User $user, InstallmentPayment $model): bool
    {
        return $user->can('update_installment_payment');
    }

    public function delete(User $user, InstallmentPayment $model): bool
    {
        return $user->can('delete_installment_payment');
    }
}
