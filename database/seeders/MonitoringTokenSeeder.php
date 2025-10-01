<?php

namespace Database\Seeders;

use App\Models\MonitoringToken;
use App\Modules\Helpdesk\Models\Tenant;
use Illuminate\Database\Seeder;

class MonitoringTokenSeeder extends Seeder
{
    public function run(): void
    {
        if (MonitoringToken::query()->exists()) {
            return;
        }

        $tenant = Tenant::query()->first();

        $rawToken = config('monitoring.default_token', 'changeme-monitor');

        MonitoringToken::create([
            'tenant_id' => $tenant?->id,
            'name' => 'Primary observability agent',
            'token_hash' => hash('sha256', $rawToken),
            'scopes' => ['health:read'],
        ]);
    }
}
