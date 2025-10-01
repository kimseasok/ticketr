<?php

namespace Database\Seeders;

use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::firstOrCreate([
            'slug' => 'acme',
        ], [
            'name' => 'ACME Inc.',
            'timezone' => 'UTC',
        ]);

        Brand::firstOrCreate([
            'tenant_id' => $tenant->id,
            'slug' => 'acme-support',
        ], [
            'name' => 'ACME Support',
            'domain' => 'https://support.acme.test',
            'primary_color' => '#0ea5e9',
            'secondary_color' => '#0369a1',
            'accent_color' => '#f97316',
            'portal_domain' => 'https://portal.acme.test',
        ]);

        $tenant = Tenant::firstOrCreate([
            'slug' => 'globex',
        ], [
            'name' => 'Globex Corporation',
            'timezone' => 'UTC',
        ]);

        Brand::firstOrCreate([
            'tenant_id' => $tenant->id,
            'slug' => 'globex-support',
        ], [
            'name' => 'Globex Support',
            'domain' => 'https://support.globex.test',
            'primary_color' => '#4f46e5',
            'secondary_color' => '#312e81',
            'accent_color' => '#facc15',
            'portal_domain' => 'https://portal.globex.test',
        ]);

        Brand::firstOrCreate([
            'tenant_id' => Tenant::query()->value('id'),
            'slug' => 'default',
        ], [
            'name' => 'Default Support Portal',
            'domain' => 'https://support.ticketr.test',
            'primary_color' => '#2563eb',
            'secondary_color' => '#1d4ed8',
            'accent_color' => '#f97316',
            'portal_domain' => 'https://portal.ticketr.test',
        ]);
    }
}
