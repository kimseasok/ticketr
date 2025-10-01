<?php

namespace App\Policies;

use App\Models\User;

class UserSecurityPolicy extends BaseTenantPolicy
{
    public function manage(User $actor, User $target): bool
    {
        if ($actor->id === $target->id) {
            return true;
        }

        if ($actor->hasRole('Admin') && $actor->tenant_id === $target->tenant_id) {
            return true;
        }

        return false;
    }
}
