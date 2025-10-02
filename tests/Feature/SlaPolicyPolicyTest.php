<?php

namespace Tests\Feature;

use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\SlaPolicy;
use App\Modules\Helpdesk\Models\Tenant;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SlaPolicyPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @group TKT-AUT-MD-02
     */
    public function test_policy_allows_admin_and_agent(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $policy = SlaPolicy::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);

        $admin = \App\Models\User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $admin->assignRole('Admin');

        $agent = \App\Models\User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $agent->assignRole('Agent');

        $viewer = \App\Models\User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $viewer->assignRole('Viewer');

        $this->assertTrue(Gate::forUser($admin)->allows('update', $policy));
        $this->assertTrue(Gate::forUser($agent)->allows('update', $policy));
        $this->assertFalse(Gate::forUser($viewer)->allows('update', $policy));
    }
}
