<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Helpdesk\Models\EmailInboundMessage;

class EmailInboundMessagePolicy extends BaseTenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('email-messages.view');
    }

    public function view(User $user, EmailInboundMessage $message): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $user->can('email-messages.view')
            && $this->sharesTenant($user, $message->tenant_id)
            && $this->sharesBrand($user, $message->brand_id);
    }
}
