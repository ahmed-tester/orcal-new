<?php

namespace App\Policies;

use App\Models\ContractVoucher;
use App\Models\User;

class ContractVoucherPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_contract_voucher') || $user->can('view_any_offer');
    }

    public function view(User $user, ContractVoucher $model): bool
    {
        return $user->can('view_any_contract_voucher') || $user->can('view_any_offer');
    }

    public function create(User $user): bool
    {
        return $user->can('create_contract_voucher') || $user->can('create_offer');
    }

    public function update(User $user, ContractVoucher $model): bool
    {
        return $user->can('update_contract_voucher') || $user->can('update_offer');
    }

    public function delete(User $user, ContractVoucher $model): bool
    {
        return $user->can('delete_contract_voucher') || $user->can('delete_offer');
    }
}
