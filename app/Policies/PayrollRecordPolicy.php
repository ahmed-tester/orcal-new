<?php

namespace App\Policies;

use App\Models\PayrollRecord;
use App\Models\User;

class PayrollRecordPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_payroll_record');
    }

    public function view(User $user, PayrollRecord $model): bool
    {
        return $user->can('view_any_payroll_record');
    }

    public function create(User $user): bool
    {
        return $user->can('create_payroll_record');
    }

    public function update(User $user, PayrollRecord $model): bool
    {
        return $user->can('update_payroll_record');
    }

    public function delete(User $user, PayrollRecord $model): bool
    {
        return $user->can('delete_payroll_record');
    }
}
