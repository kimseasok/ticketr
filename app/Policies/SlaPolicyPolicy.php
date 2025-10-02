<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Helpdesk\Models\SlaPolicy;

class SlaPolicyPolicy extends BaseTenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || ($this->isAgent($user) && $user->can('sla-policies.view'));
    }

    public function view(User $user, SlaPolicy $policy): bool
    {
        if (! $this->sharesTenant($user, $policy->tenant_id)) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->can('sla-policies.view')) {
            return $this->sharesBrand($user, $policy->brand_id);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return ($this->isAdmin($user) || $this->isAgent($user)) && $user->can('sla-policies.manage');
    }

    public function update(User $user, SlaPolicy $policy): bool
    {
        if (! $this->sharesTenant($user, $policy->tenant_id)) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->isAgent($user)
            && $user->can('sla-policies.manage')
            && $this->sharesBrand($user, $policy->brand_id);
    }

    public function delete(User $user, SlaPolicy $policy): bool
    {
        return $this->update($user, $policy);
    }
}
