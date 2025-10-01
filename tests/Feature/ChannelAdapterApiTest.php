<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ChannelAdapterApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @group A2-SD-01
     */
    public function test_admin_can_crud_channel_adapters(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();

        app(TenantContext::class)->setTenantId($tenant->id);
        app(TenantContext::class)->setBrandId($brand->id);

        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $admin->assignRole('Admin');

        $headers = [
            config('tenancy.tenant_header') => $tenant->id,
            config('tenancy.brand_header') => $brand->id,
        ];

        $payload = [
            'name' => 'Webhook Adapter',
            'slug' => 'webhook-adapter',
            'channel' => 'web',
            'provider' => 'custom',
            'configuration' => ['endpoint' => 'https://example.test/hook'],
            'metadata' => ['region' => 'us-east'],
        ];

        $this->actingAs($admin);

        $create = $this->withHeaders($headers)->postJson('/api/channel-adapters', $payload);
        $create->assertCreated();
        $adapterId = $create->json('data.id');

        $this->withHeaders($headers)
            ->getJson('/api/channel-adapters')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'webhook-adapter']);

        $this->withHeaders($headers)
            ->putJson("/api/channel-adapters/{$adapterId}", [
                'provider' => 'updated-provider',
            ])
            ->assertOk()
            ->assertJsonFragment(['provider' => 'updated-provider']);

        $this->withHeaders($headers)
            ->deleteJson("/api/channel-adapters/{$adapterId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('channel_adapters', [
            'id' => $adapterId,
        ]);
    }

    /**
     * @group A2-SD-01
     */
    public function test_viewer_cannot_create_adapter(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();

        app(TenantContext::class)->setTenantId($tenant->id);
        app(TenantContext::class)->setBrandId($brand->id);

        $viewer = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $viewer->assignRole('Viewer');

        $headers = [
            config('tenancy.tenant_header') => $tenant->id,
            config('tenancy.brand_header') => $brand->id,
        ];

        $this->actingAs($viewer);

        $this->withHeaders($headers)
            ->postJson('/api/channel-adapters', [
                'name' => 'Forbidden Adapter',
                'slug' => 'forbidden-adapter',
                'channel' => 'email',
                'provider' => 'smtp',
            ])
            ->assertForbidden();
    }
}
