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
            'domain' => 'support.acme.test',
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
            'domain' => 'support.globex.test',
        ]);
    }
}
