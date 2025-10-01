<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Helpdesk\Models\EmailOutboundMessage;

class EmailOutboundMessagePolicy extends BaseTenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('email-messages.view');
    }

    public function view(User $user, EmailOutboundMessage $message): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $user->can('email-messages.view')
            && $this->sharesTenant($user, $message->tenant_id)
            && $this->sharesBrand($user, $message->brand_id);
    }

    public function deliver(User $user, EmailOutboundMessage $message): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $user->can('email-pipeline.deliver')
            && $this->sharesTenant($user, $message->tenant_id)
            && $this->sharesBrand($user, $message->brand_id);
    }
}
