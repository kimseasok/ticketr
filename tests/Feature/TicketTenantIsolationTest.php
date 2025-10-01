<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Helpdesk\Filament\Resources\TicketResource\Pages\ListTickets;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\Ticket;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TicketLifecycleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TicketTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @group A1-TS-01
     */
    public function test_api_crud_requests_are_scoped_to_authenticated_tenant(): void
    {
        $tenantA = Tenant::factory()->create();
        $brandA = Brand::factory()->for($tenantA)->create();
        $tenantB = Tenant::factory()->create();
        $brandB = Brand::factory()->for($tenantB)->create();

        app(TicketLifecycleSeeder::class)->runForTenant($tenantA->id);
        app(TicketLifecycleSeeder::class)->runForTenant($tenantB->id);

        $userA = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'brand_id' => $brandA->id,
        ]);
        $userA->assignRole('Admin');

        $ticketB = Ticket::factory()->create([
            'tenant_id' => $tenantB->id,
            'brand_id' => $brandB->id,
            'first_response_due_at' => now()->addHour(),
            'resolution_due_at' => now()->addHours(4),
        ]);

        $headers = [
            config('tenancy.tenant_header') => $tenantA->id,
            config('tenancy.brand_header') => $brandA->id,
        ];

        $this->actingAs($userA);

        $createResponse = $this->withHeaders($headers)->postJson('/api/tickets', [
            'brand_id' => $brandA->id,
            'contact_id' => null,
            'company_id' => null,
            'subject' => 'Tenant scoped',
            'priority' => 'normal',
            'channel' => 'email',
        ]);

        $createResponse->assertCreated();
        $this->assertDatabaseHas('tickets', [
            'id' => $createResponse->json('data.id'),
            'tenant_id' => $tenantA->id,
        ]);

        $this->withHeaders($headers)
            ->getJson("/api/tickets/{$ticketB->id}")
            ->assertNotFound();

        $this->withHeaders($headers)
            ->putJson("/api/tickets/{$ticketB->id}", ['status' => Ticket::STATUS_PENDING])
            ->assertNotFound();

        $this->withHeaders($headers)
            ->postJson('/api/tickets', [
                'brand_id' => $brandB->id,
                'tenant_id' => $tenantB->id,
                'subject' => 'Should fail',
                'priority' => 'normal',
            ])
            ->assertStatus(403);
    }

    /**
     * @group A1-TS-01
     */
    public function test_filament_listing_only_shows_tickets_for_active_context(): void
    {
        $tenantA = Tenant::factory()->create();
        $brandA = Brand::factory()->for($tenantA)->create();
        $tenantB = Tenant::factory()->create();
        $brandB = Brand::factory()->for($tenantB)->create();

        app(TicketLifecycleSeeder::class)->runForTenant($tenantA->id);
        app(TicketLifecycleSeeder::class)->runForTenant($tenantB->id);

        $user = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'brand_id' => $brandA->id,
        ]);
        $user->assignRole('Agent');

        Ticket::factory()->create([
            'tenant_id' => $tenantA->id,
            'brand_id' => $brandA->id,
            'subject' => 'Tenant A Ticket',
        ]);

        Ticket::factory()->create([
            'tenant_id' => $tenantB->id,
            'brand_id' => $brandB->id,
            'subject' => 'Tenant B Ticket',
        ]);

        app(TenantContext::class)->setTenantId($tenantA->id);
        app(TenantContext::class)->setBrandId($brandA->id);

        Livewire::actingAs($user)
            ->test(ListTickets::class)
            ->assertSee('Tenant A Ticket')
            ->assertDontSee('Tenant B Ticket');
    }
}
