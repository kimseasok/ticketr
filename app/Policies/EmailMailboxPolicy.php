<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Helpdesk\Models\EmailMailbox;

class EmailMailboxPolicy extends BaseTenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('email-mailboxes.view');
    }

    public function view(User $user, EmailMailbox $mailbox): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $user->can('email-mailboxes.view')
            && $this->sharesTenant($user, $mailbox->tenant_id)
            && $this->sharesBrand($user, $mailbox->brand_id);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user) || ($this->isAgent($user) && $user->can('email-mailboxes.manage'));
    }

    public function update(User $user, EmailMailbox $mailbox): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->isAgent($user)
            && $user->can('email-mailboxes.manage')
            && $this->sharesTenant($user, $mailbox->tenant_id)
            && $this->sharesBrand($user, $mailbox->brand_id);
    }

    public function delete(User $user, EmailMailbox $mailbox): bool
    {
        return $this->update($user, $mailbox);
    }

    public function sync(User $user, EmailMailbox $mailbox): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $user->can('email-pipeline.sync')
            && $this->sharesTenant($user, $mailbox->tenant_id)
            && $this->sharesBrand($user, $mailbox->brand_id);
    }
}
