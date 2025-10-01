<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\Ticket;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TicketLifecycleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TicketBulkActionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @test @group A2-TS-01 */
    public function agent_can_apply_bulk_actions_via_api(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();

        app(TicketLifecycleSeeder::class)->runForTenant($tenant->id);

        $agent = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $agent->assignRole('Agent');

        $tickets = Ticket::factory()->count(2)->forBrand($brand)->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'status' => Ticket::STATUS_OPEN,
            'priority' => 'high',
        ]);

        $payload = [
            'ticket_ids' => $tickets->pluck('id')->all(),
            'actions' => [
                [
                    'type' => 'assign',
                    'assignee_id' => $agent->id,
                ],
                [
                    'type' => 'status',
                    'status' => Ticket::STATUS_PENDING,
                ],
                [
                    'type' => 'sla',
                    'resolution_due_at' => now()->addDay()->toDateTimeString(),
                ],
            ],
        ];

        $this->actingAs($agent);

        $headers = [
            config('tenancy.tenant_header') => $tenant->id,
            config('tenancy.brand_header') => $brand->id,
        ];

        $response = $this->withHeaders($headers)
            ->postJson('/api/tickets/bulk-actions', $payload);

        $response->assertStatus(202)
            ->assertJsonPath('data.processed', 2)
            ->assertJsonPath('data.skipped', 0);

        foreach ($tickets as $ticket) {
            $this->assertDatabaseHas('tickets', [
                'id' => $ticket->id,
                'assigned_to' => $agent->id,
                'status' => Ticket::STATUS_PENDING,
            ]);
        }
    }

    /** @test @group A2-TS-01 */
    public function viewer_cannot_trigger_bulk_actions(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();

        app(TicketLifecycleSeeder::class)->runForTenant($tenant->id);

        $viewer = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $viewer->assignRole('Viewer');

        $ticket = Ticket::factory()->forBrand($brand)->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);

        $this->actingAs($viewer);

        $headers = [
            config('tenancy.tenant_header') => $tenant->id,
            config('tenancy.brand_header') => $brand->id,
        ];

        $this->withHeaders($headers)
            ->postJson('/api/tickets/bulk-actions', [
                'ticket_ids' => [$ticket->id],
                'actions' => [
                    [
                        'type' => 'status',
                        'status' => Ticket::STATUS_PENDING,
                    ],
                ],
            ])
            ->assertForbidden();
    }
}
