<?php

namespace App\Policies;

use App\Models\RoscaFundPayment;
use App\Models\User;

class RoscaFundPaymentPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_rosca_fund_payment');
    }

    public function view(User $user, RoscaFundPayment $model): bool
    {
        return $user->can('view_any_rosca_fund_payment');
    }

    public function create(User $user): bool
    {
        return $user->can('create_rosca_fund_payment');
    }

    public function update(User $user, RoscaFundPayment $model): bool
    {
        return $user->can('update_rosca_fund_payment');
    }

    public function delete(User $user, RoscaFundPayment $model): bool
    {
        return $user->can('delete_rosca_fund_payment');
    }
}
