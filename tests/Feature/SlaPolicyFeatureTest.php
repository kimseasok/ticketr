<?php

namespace Tests\Feature;

use App\Modules\Helpdesk\Filament\Resources\SlaPolicyResource\Pages\CreateSlaPolicy;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\SlaPolicy;
use App\Modules\Helpdesk\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SlaPolicyFeatureTest extends TestCase
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
    public function test_admin_can_manage_sla_policies(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();

        app(TenantContext::class)->setTenantId($tenant->id);
        app(TenantContext::class)->setBrandId($brand->id);

        $admin = $this->createUserWithRole('Admin', $tenant->id, $brand->id);

        $headers = [
            config('tenancy.tenant_header') => $tenant->id,
            config('tenancy.brand_header') => $brand->id,
        ];

        $payload = [
            'name' => 'VIP SLA',
            'slug' => 'vip-sla',
            'description' => 'Faster responses for VIP customers.',
            'priority_scope' => 'high',
            'channel_scope' => 'email',
            'first_response_minutes' => 60,
            'resolution_minutes' => 240,
            'grace_minutes' => 10,
            'alert_after_minutes' => 45,
        ];

        $this->actingAs($admin)
            ->withHeaders($headers)
            ->postJson('/api/automation/sla-policies', $payload)
            ->assertCreated();

        $policyId = SlaPolicy::where('slug', 'vip-sla')->value('id');
        $this->assertNotNull($policyId);

        $this->withHeaders($headers)
            ->putJson("/api/automation/sla-policies/{$policyId}", [
                'grace_minutes' => 20,
                'is_active' => false,
            ])
            ->assertOk()
            ->assertJsonFragment(['grace_minutes' => 20]);

        $this->withHeaders($headers)
            ->deleteJson("/api/automation/sla-policies/{$policyId}")
            ->assertNoContent();

        $this->assertSoftDeleted('sla_policies', ['id' => $policyId]);
    }

    /**
     * @group TKT-AUT-MD-02
     */
    public function test_filament_can_create_sla_policy(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();

        app(TenantContext::class)->setTenantId($tenant->id);
        app(TenantContext::class)->setBrandId($brand->id);

        $admin = $this->createUserWithRole('Admin', $tenant->id, $brand->id);

        Livewire::actingAs($admin)
            ->test(CreateSlaPolicy::class)
            ->set('data', [
                'tenant_id' => $tenant->id,
                'brand_id' => $brand->id,
                'name' => 'Filament SLA',
                'slug' => 'filament-sla',
                'first_response_minutes' => 90,
                'resolution_minutes' => 360,
                'grace_minutes' => 15,
                'alert_after_minutes' => 60,
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('sla_policies', [
            'slug' => 'filament-sla',
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * @group TKT-AUT-MD-02
     */
    public function test_viewer_cannot_delete_sla_policies(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $policy = SlaPolicy::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);

        app(TenantContext::class)->setTenantId($tenant->id);
        app(TenantContext::class)->setBrandId($brand->id);

        $viewer = $this->createUserWithRole('Viewer', $tenant->id, $brand->id);

        $headers = [
            config('tenancy.tenant_header') => $tenant->id,
            config('tenancy.brand_header') => $brand->id,
        ];

        $this->actingAs($viewer)
            ->withHeaders($headers)
            ->deleteJson("/api/automation/sla-policies/{$policy->id}")
            ->assertForbidden();
    }

    private function createUserWithRole(string $role, int $tenantId, ?int $brandId)
    {
        $user = \App\Models\User::factory()->create([
            'tenant_id' => $tenantId,
            'brand_id' => $brandId,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
