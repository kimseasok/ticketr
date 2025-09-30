<?php

namespace Spatie\Permission;

class PermissionRegistrar
{
    public function forgetCachedPermissions(): void
    {
        // No-op cache implementation for offline environment.
    }
}
