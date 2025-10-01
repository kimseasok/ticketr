<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Helpdesk\Models\ChannelAdapter;

class ChannelAdapterPolicy extends BaseTenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user) || $user->can('channel-adapters.view');
    }

    public function view(User $user, ChannelAdapter $adapter): bool
    {
        if (! $this->sharesTenant($user, $adapter->tenant_id)) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->can('channel-adapters.manage') && $this->isAgent($user)) {
            return $this->sharesBrand($user, $adapter->brand_id);
        }

        return $user->can('channel-adapters.view') && $this->sharesBrand($user, $adapter->brand_id);
    }

    public function create(User $user): bool
    {
        return ($this->isAdmin($user) || $this->isAgent($user)) && $user->can('channel-adapters.manage');
    }

    public function update(User $user, ChannelAdapter $adapter): bool
    {
        if (! $this->sharesTenant($user, $adapter->tenant_id)) {
            return false;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->isAgent($user)
            && $user->can('channel-adapters.manage')
            && $this->sharesBrand($user, $adapter->brand_id);
    }

    public function delete(User $user, ChannelAdapter $adapter): bool
    {
        return $this->update($user, $adapter);
    }
}
