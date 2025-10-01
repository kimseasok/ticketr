<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\TicketMacro;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TicketMacroApiTest extends TestCase
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
    public function test_agent_can_manage_macros(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();

        app(TenantContext::class)->setTenantId($tenant->id);
        app(TenantContext::class)->setBrandId($brand->id);

        $agent = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $agent->assignRole('Agent');

        $headers = [
            config('tenancy.tenant_header') => $tenant->id,
            config('tenancy.brand_header') => $brand->id,
        ];

        $this->actingAs($agent);

        $payload = [
            'name' => 'Escalate',
            'slug' => 'escalate',
            'body' => 'Escalating ticket to tier 2.',
            'visibility' => 'brand',
        ];

        $create = $this->withHeaders($headers)->postJson('/api/ticket-macros', $payload);
        $create->assertCreated();
        $macroId = $create->json('data.id');

        $this->withHeaders($headers)
            ->getJson('/api/ticket-macros')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'escalate']);

        $this->withHeaders($headers)
            ->putJson("/api/ticket-macros/{$macroId}", [
                'body' => 'Updated body',
                'visibility' => 'tenant',
            ])
            ->assertOk()
            ->assertJsonFragment(['body' => 'Updated body']);

        $this->withHeaders($headers)
            ->deleteJson("/api/ticket-macros/{$macroId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('ticket_macros', [
            'id' => $macroId,
        ]);
    }

    /**
     * @group A2-SD-01
     */
    public function test_private_macros_capture_owner_metadata(): void
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

        $this->actingAs($admin);

        $response = $this->withHeaders($headers)->postJson('/api/ticket-macros', [
            'name' => 'Private Draft',
            'slug' => 'private-draft',
            'body' => 'Hidden macro',
            'visibility' => 'private',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('ticket_macros', [
            'slug' => 'private-draft',
            'tenant_id' => $tenant->id,
        ]);

        $metadata = TicketMacro::where('slug', 'private-draft')->firstOrFail()->metadata;
        $this->assertSame($admin->id, $metadata['owner_id'] ?? null);
    }

    /**
     * @group A2-SD-01
     */
    public function test_viewer_cannot_delete_macros(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();

        app(TenantContext::class)->setTenantId($tenant->id);
        app(TenantContext::class)->setBrandId($brand->id);

        $macro = TicketMacro::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'visibility' => 'tenant',
        ]);

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
            ->deleteJson("/api/ticket-macros/{$macro->id}")
            ->assertForbidden();
    }
}
