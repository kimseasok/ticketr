<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Models\TicketMessage;

class TicketMessagePolicy extends BaseTenantPolicy
{
    public function viewAny(User $user, ?Ticket $ticket = null): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if (! $user->can('ticket-messages.view')) {
            return false;
        }

        if ($ticket === null) {
            return true;
        }

        return $this->sharesTenant($user, $ticket->tenant_id)
            && $this->sharesBrand($user, $ticket->brand_id);
    }

    public function view(User $user, TicketMessage $message): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if (! $user->can('ticket-messages.view')) {
            return false;
        }

        return $this->sharesTenant($user, $message->tenant_id)
            && $this->sharesBrand($user, $message->brand_id);
    }

    public function create(User $user, ?Ticket $ticket = null): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($ticket === null) {
            return $this->isAgent($user)
                && $user->can('ticket-messages.manage');
        }

        return $this->isAgent($user)
            && $user->can('ticket-messages.manage')
            && $this->sharesTenant($user, $ticket->tenant_id)
            && $this->sharesBrand($user, $ticket->brand_id);
    }

    public function update(User $user, TicketMessage $message): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->isAgent($user)
            && $user->can('ticket-messages.manage')
            && $this->sharesTenant($user, $message->tenant_id)
            && $this->sharesBrand($user, $message->brand_id);
    }

    public function delete(User $user, TicketMessage $message): bool
    {
        return $this->update($user, $message);
    }
}
