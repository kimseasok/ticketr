<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Helpdesk\Models\Contact;

class ContactPolicy extends BaseTenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $this->isAgent($user) || $this->isViewer($user);
    }

    public function view(User $user, Contact $contact): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if (! $this->sharesTenant($user, $contact->tenant_id)) {
            return false;
        }

        return $this->sharesBrand($user, $contact->brand_id);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user) || $this->isAgent($user);
    }

    public function update(User $user, Contact $contact): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->isAgent($user)
            && $this->sharesTenant($user, $contact->tenant_id)
            && $this->sharesBrand($user, $contact->brand_id);
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $this->update($user, $contact);
    }
}
