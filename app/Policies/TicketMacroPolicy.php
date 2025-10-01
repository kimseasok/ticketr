<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Helpdesk\Models\TicketMacro;

class TicketMacroPolicy extends BaseTenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('ticket-macros.view');
    }

    public function view(User $user, TicketMacro $macro): bool
    {
        if (! $this->sharesTenant($user, $macro->tenant_id)) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        if ($macro->visibility === 'private' && $macro->metadata['owner_id'] ?? null !== $user->id) {
            return false;
        }

        if ($user->can('ticket-macros.manage') && $this->isAgent($user)) {
            return $this->sharesBrand($user, $macro->brand_id);
        }

        return $user->can('ticket-macros.view') && $this->sharesBrand($user, $macro->brand_id);
    }

    public function create(User $user): bool
    {
        return ($this->isAdmin($user) || $this->isAgent($user)) && $user->can('ticket-macros.manage');
    }

    public function update(User $user, TicketMacro $macro): bool
    {
        if (! $this->sharesTenant($user, $macro->tenant_id)) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->isAgent($user)
            && $user->can('ticket-macros.manage')
            && $this->sharesBrand($user, $macro->brand_id);
    }

    public function delete(User $user, TicketMacro $macro): bool
    {
        return $this->update($user, $macro);
    }
}
