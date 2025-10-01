<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Tenant;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserSecurityPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @group Issue-11
     */
    public function test_admin_can_manage_security_for_tenant_users(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'brand_id' => $brand->id]);
        $admin->assignRole('Admin');

        $target = User::factory()->create(['tenant_id' => $tenant->id, 'brand_id' => $brand->id]);

        $this->assertTrue(Gate::forUser($admin)->allows('manage', $target));
    }

    /**
     * @group Issue-11
     */
    public function test_agent_cannot_manage_other_users_security(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $agent = User::factory()->create(['tenant_id' => $tenant->id, 'brand_id' => $brand->id]);
        $agent->assignRole('Agent');

        $other = User::factory()->create(['tenant_id' => $tenant->id, 'brand_id' => $brand->id]);

        $this->assertFalse(Gate::forUser($agent)->allows('manage', $other));
        $this->assertTrue(Gate::forUser($agent)->allows('manage', $agent));
    }

    /**
     * @group Issue-11
     */
    public function test_admin_cannot_manage_cross_tenant_users(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'brand_id' => $brand->id]);
        $admin->assignRole('Admin');

        $otherTenant = Tenant::factory()->create();
        $otherBrand = Brand::factory()->for($otherTenant)->create();
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id, 'brand_id' => $otherBrand->id]);

        $this->assertFalse(Gate::forUser($admin)->allows('manage', $otherUser));
    }
}
