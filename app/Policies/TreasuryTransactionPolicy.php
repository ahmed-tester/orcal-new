<?php

namespace App\Policies;

use App\Models\TreasuryTransaction;
use App\Models\User;

class TreasuryTransactionPolicy
{
    use HasSuperAdminBypass;

    public function before(User $user, string $ability): ?bool
    {
        // منع الحذف نهائياً لجميع المستخدمين بما فيهم السوبر أدمن
        if ($ability === 'delete' || $ability === 'deleteAny') {
            return false;
        }

        if ($user->isSuperAdminOrOwner()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_treasury_transaction');
    }

    public function view(User $user, TreasuryTransaction $model): bool
    {
        return $user->can('view_any_treasury_transaction');
    }

    public function create(User $user): bool
    {
        return $user->can('create_treasury_transaction');
    }

    public function update(User $user, TreasuryTransaction $model): bool
    {
        return $user->can('update_treasury_transaction');
    }

    public function delete(User $user, TreasuryTransaction $model): bool
    {
        // تم تعطيل حذف أي حركة من حركات الخزينة لحماية التوازن المالي والتسلسل المحاسبي
        // return $user->can('delete_treasury_transaction');
        return false;
    }

    public function deleteAny(User $user): bool
    {
        // تم تعطيل الحذف الجماعي لحركات الخزينة
        // return $user->can('delete_treasury_transaction');
        return false;
    }
}
