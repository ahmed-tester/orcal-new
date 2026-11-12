<?php

namespace App\Policies;

use App\Models\TreasuryTransaction;
use App\Models\User;
use App\Services\FinancialSurplusRefundService;

class TreasuryTransactionPolicy
{
    use HasSuperAdminBypass;

    public function before(User $user, string $ability, mixed $record = null): ?bool
    {
        if ($ability === 'update' && $record instanceof TreasuryTransaction
            && ($record->metadata['source'] ?? null) === FinancialSurplusRefundService::TREASURY_SOURCE) {
            return false;
        }

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
        // return $user->can('delete_treasury_transaction');
        return false;
    }

    public function deleteAny(User $user): bool
    {
        // return $user->can('delete_treasury_transaction');
        return false;
    }
}
