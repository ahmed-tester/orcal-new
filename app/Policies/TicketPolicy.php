<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    use HasSuperAdminBypass;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->can('manage_tickets')
            || $user->can('view_any_ticket')
            || $user->id === $ticket->user_id
            || $user->id === $ticket->assigned_to;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->can('manage_tickets')
            || $user->can('update_ticket')
            || $user->id === $ticket->user_id;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->can('delete_ticket') || $user->can('manage_tickets');
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        return $this->view($user, $ticket);
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->can('manage_tickets') || $user->can('update_ticket');
    }
}
