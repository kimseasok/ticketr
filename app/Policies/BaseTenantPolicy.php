<?php

namespace App\Policies;

use App\Models\User;

abstract class BaseTenantPolicy
{
    protected function isAdmin(User $user): bool
    {
        return $user->hasRole('Admin');
    }

    protected function isAgent(User $user): bool
    {
        return $user->hasRole('Agent');
    }

    protected function isViewer(User $user): bool
    {
        return $user->hasRole('Viewer');
    }

    protected function sharesTenant(User $user, ?int $tenantId): bool
    {
        return $tenantId === null || $user->tenant_id === $tenantId;
    }

    protected function sharesBrand(User $user, ?int $brandId): bool
    {
        return $brandId === null || $user->brand_id === $brandId;
    }
}
