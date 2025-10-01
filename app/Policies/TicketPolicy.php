<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Helpdesk\Models\Ticket;

class TicketPolicy extends BaseTenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('tickets.view');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if (! $user->can('tickets.view')) {
            return false;
        }

        if (! $this->sharesTenant($user, $ticket->tenant_id)) {
            return false;
        }

        if ($this->isAgent($user)) {
            return $this->sharesBrand($user, $ticket->brand_id);
        }

        return $this->isViewer($user) && $this->sharesBrand($user, $ticket->brand_id);
    }

    public function create(User $user): bool
    {
        return ($this->isAdmin($user) || $this->isAgent($user)) && $user->can('tickets.manage');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->isAgent($user)
            && $user->can('tickets.manage')
            && $this->sharesTenant($user, $ticket->tenant_id)
            && $this->sharesBrand($user, $ticket->brand_id);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $this->update($user, $ticket);
    }

    public function assign(User $user, Ticket|string|null $ticket = null): bool
    {
        if (! $user->can('tickets.assign')) {
            return false;
        }

        if ($this->isAdmin($user)) {
            if ($ticket instanceof Ticket) {
                return $this->sharesTenant($user, $ticket->tenant_id);
            }

            return true;
        }

        if (! $this->isAgent($user)) {
            return false;
        }

        if (! $ticket instanceof Ticket) {
            return true;
        }

        return $this->sharesTenant($user, $ticket->tenant_id)
            && $this->sharesBrand($user, $ticket->brand_id);
    }

    public function manageWatchers(User $user, Ticket $ticket): bool
    {
        if (! $user->can('tickets.watchers.manage')) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return $this->sharesTenant($user, $ticket->tenant_id);
        }

        return $this->isAgent($user)
            && $this->sharesTenant($user, $ticket->tenant_id)
            && $this->sharesBrand($user, $ticket->brand_id);
    }
}
