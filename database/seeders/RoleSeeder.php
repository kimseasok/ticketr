<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'tickets.manage',
            'tickets.view',
            'contacts.manage',
            'contacts.view',
            'knowledge-base.manage',
            'knowledge-base.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $admin = Role::firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);
        $agent = Role::firstOrCreate([
            'name' => 'Agent',
            'guard_name' => 'web',
        ]);
        $viewer = Role::firstOrCreate([
            'name' => 'Viewer',
            'guard_name' => 'web',
        ]);

        $admin->syncPermissions($permissions);
        $agent->syncPermissions([
            'tickets.manage',
            'tickets.view',
            'contacts.manage',
            'contacts.view',
            'knowledge-base.manage',
            'knowledge-base.view',
        ]);
        $viewer->syncPermissions([
            'tickets.view',
            'contacts.view',
            'knowledge-base.view',
        ]);
    }
}
