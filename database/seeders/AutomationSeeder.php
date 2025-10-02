<?php

namespace Database\Seeders;

use App\Modules\Helpdesk\Models\AutomationRule;
use App\Modules\Helpdesk\Models\SlaPolicy;
use App\Modules\Helpdesk\Models\Tenant;
use Illuminate\Database\Seeder;

class AutomationSeeder extends Seeder
{
    public function run(): void
    {
        Tenant::query()->with('brands')->each(function (Tenant $tenant): void {
            $brand = $tenant->brands->first();

            $policy = SlaPolicy::firstOrCreate([
                'tenant_id' => $tenant->id,
                'slug' => 'default-response',
            ], [
                'brand_id' => $brand?->id,
                'name' => 'Default Response SLA',
                'description' => 'Respond to new tickets within two hours.',
                'priority_scope' => null,
                'channel_scope' => null,
                'first_response_minutes' => 120,
                'resolution_minutes' => 480,
                'grace_minutes' => 15,
                'alert_after_minutes' => 60,
                'is_active' => true,
            ]);

            AutomationRule::firstOrCreate([
                'tenant_id' => $tenant->id,
                'slug' => 'auto-assign-priority',
            ], [
                'brand_id' => $brand?->id,
                'name' => 'Auto-assign High Priority',
                'event' => 'ticket.created',
                'match_type' => 'all',
                'conditions' => [
                    ['field' => 'channel', 'operator' => 'equals', 'value' => 'email'],
                ],
                'actions' => [
                    ['type' => 'set_priority', 'value' => 'high'],
                    ['type' => 'apply_sla', 'sla_policy_id' => $policy->id],
                ],
                'is_active' => true,
                'run_order' => 1,
            ]);
        });
    }
}
