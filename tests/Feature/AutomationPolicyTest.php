<?php

namespace Tests\Feature;

use App\Modules\Helpdesk\Models\AutomationRule;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Tenant;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AutomationPolicyTest extends TestCase
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
    public function test_policy_matrix(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $rule = AutomationRule::factory()->create([
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

        $this->assertTrue(Gate::forUser($admin)->allows('view', $rule));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $rule));

        $this->assertTrue(Gate::forUser($agent)->allows('view', $rule));
        $this->assertTrue(Gate::forUser($agent)->allows('update', $rule));

        $this->assertTrue(Gate::forUser($viewer)->allows('view', $rule));
        $this->assertFalse(Gate::forUser($viewer)->allows('delete', $rule));
    }
}
