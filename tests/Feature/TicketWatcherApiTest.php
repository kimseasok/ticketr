<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Contact;
use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Models\TicketCategory;
use App\Modules\Helpdesk\Models\TicketTag;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TicketWatcherApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @group A1-RB-01
     */
    public function test_agent_can_manage_watchers_and_assignment(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $contact = Contact::factory()->forBrand($brand)->create();
        $category = TicketCategory::factory()->forTenant($tenant)->create();
        $tag = TicketTag::factory()->forTenant($tenant)->create();

        app(TenantContext::class)->setTenantId($tenant->id);
        app(TenantContext::class)->setBrandId($brand->id);

        $agent = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $agent->assignRole('Agent');

        $watcher = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $otherBrand = Brand::factory()->for($tenant)->create();
        $otherWatcher = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $otherBrand->id,
        ]);

        $ticket = Ticket::factory()->for($contact)->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $ticket->syncCategories([$category->id], $agent->id);
        $ticket->syncTags([$tag->id], $agent->id);

        $headers = [
            config('tenancy.tenant_header') => $tenant->id,
            config('tenancy.brand_header') => $brand->id,
        ];

        $this->actingAs($agent);

        $response = $this->withHeaders($headers)->putJson("/api/tickets/{$ticket->id}", [
            'assigned_to' => $agent->id,
            'watcher_ids' => [$watcher->id, $otherWatcher->id],
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['assigned_to' => $agent->id]);
        $response->assertJsonFragment(['user_id' => $watcher->id]);
        $response->assertJsonMissing(['user_id' => $otherWatcher->id]);

        $this->assertDatabaseHas('ticket_participants', [
            'ticket_id' => $ticket->id,
            'participant_id' => $watcher->id,
            'role' => 'watcher',
        ]);

        $this->assertDatabaseMissing('ticket_participants', [
            'ticket_id' => $ticket->id,
            'participant_id' => $otherWatcher->id,
            'role' => 'watcher',
        ]);
    }

    /**
     * @group A1-RB-01
     */
    public function test_viewer_cannot_manage_watchers(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $contact = Contact::factory()->forBrand($brand)->create();

        app(TenantContext::class)->setTenantId($tenant->id);
        app(TenantContext::class)->setBrandId($brand->id);

        $viewer = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $viewer->assignRole('Viewer');

        $ticket = Ticket::factory()->for($contact)->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);

        $headers = [
            config('tenancy.tenant_header') => $tenant->id,
            config('tenancy.brand_header') => $brand->id,
        ];

        $this->actingAs($viewer);

        $this->withHeaders($headers)
            ->putJson("/api/tickets/{$ticket->id}", [
                'watcher_ids' => [$viewer->id],
            ])
            ->assertForbidden();
    }
}
