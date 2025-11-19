<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Grant all abilities to admins up-front.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any tickets.
     */
    public function viewAny(User $user): bool
    {
        // Reporters can view list (controllers should scope to own tickets);
        // Agents can view all.
        return $user->hasRole(['agent', 'reporter']);
    }

    /**
     * Determine whether the user can view the ticket.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->hasRole('agent')) {
            return true;
        }

        // Reporter may view only own tickets
        if ($user->hasRole('reporter')) {
            return (int) $ticket->reporter_id === (int) $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create tickets.
     */
    public function create(User $user): bool
    {
        // All roles can create tickets (reporter creates their own)
        return $user->hasRole(['reporter', 'agent']);
    }

    /**
     * Determine whether the user can update the ticket.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        // Agents can update all tickets; reporters cannot update after creation per requirements
        return $user->hasRole(['admin', 'agent']);
    }

    /**
     * Determine whether the user can delete the ticket.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        // Only admins (handled in before) or optionally agents — keep delete restricted
        return false;
    }
}
