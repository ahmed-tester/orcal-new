<?php

namespace App\Policies;

use App\Models\AssetFundVoucher;
use App\Models\User;

class AssetFundVoucherPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_asset_fund_voucher');
    }

    public function view(User $user, AssetFundVoucher $model): bool
    {
        if ($user->can('view_any_asset_fund_voucher')) {
            return true;
        }

        $employeeId = $user->employee?->id;
        if ($employeeId && $model->fund && ($model->fund->manager_employee_id === $employeeId || $model->fund->accountant_employee_id === $employeeId)) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->can('create_asset_fund_voucher');
    }

    public function update(User $user, AssetFundVoucher $model): bool
    {
        if ($user->can('update_asset_fund_voucher')) {
            return true;
        }

        $employeeId = $user->employee?->id;
        if ($employeeId && $model->fund && ($model->fund->manager_employee_id === $employeeId || $model->fund->accountant_employee_id === $employeeId)) {
            return true;
        }

        return false;
    }

    public function delete(User $user, AssetFundVoucher $model): bool
    {
        return $user->can('delete_asset_fund_voucher');
    }
}

