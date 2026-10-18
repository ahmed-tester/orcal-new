<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isSuperAdmin();
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        return $user->isSuperAdmin();
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->isSuperAdmin();
    }
}
