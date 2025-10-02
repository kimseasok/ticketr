<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Helpdesk\Models\AutomationRule;

class AutomationRulePolicy extends BaseTenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || ($this->isAgent($user) && $user->can('automation-rules.view'));
    }

    public function view(User $user, AutomationRule $rule): bool
    {
        if (! $this->sharesTenant($user, $rule->tenant_id)) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->can('automation-rules.view')) {
            return $this->sharesBrand($user, $rule->brand_id);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return ($this->isAdmin($user) || $this->isAgent($user)) && $user->can('automation-rules.manage');
    }

    public function update(User $user, AutomationRule $rule): bool
    {
        if (! $this->sharesTenant($user, $rule->tenant_id)) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->isAgent($user)
            && $user->can('automation-rules.manage')
            && $this->sharesBrand($user, $rule->brand_id);
    }

    public function delete(User $user, AutomationRule $rule): bool
    {
        return $this->update($user, $rule);
    }
}
