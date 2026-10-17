<?php

namespace App\Policies;

use App\Models\InvoiceType;
use App\Models\User;

class InvoiceTypePolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_invoice_type');
    }

    public function view(User $user, InvoiceType $model): bool
    {
        return $user->can('view_any_invoice_type');
    }

    public function create(User $user): bool
    {
        return $user->can('create_invoice_type');
    }

    public function update(User $user, InvoiceType $model): bool
    {
        return $user->can('update_invoice_type');
    }

    public function delete(User $user, InvoiceType $model): bool
    {
        return $user->can('delete_invoice_type');
    }
}
