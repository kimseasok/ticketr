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

class TicketBulkActionFilamentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @test @group A2-TS-01 */
    public function filament_bulk_actions_apply_assign_and_status(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();

        app(TicketLifecycleSeeder::class)->runForTenant($tenant->id);

        $agent = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $agent->assignRole('Agent');

        $ticket = Ticket::factory()->forBrand($brand)->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'status' => Ticket::STATUS_OPEN,
        ]);

        app(TenantContext::class)->setTenantId($tenant->id);
        app(TenantContext::class)->setBrandId($brand->id);

        Livewire::actingAs($agent)
            ->test(ListTickets::class)
            ->callTableBulkAction('assignAgent', [$ticket->id], ['assignee_id' => $agent->id])
            ->assertHasNoTableBulkActionErrors()
            ->callTableBulkAction('updateStatus', [$ticket->id], ['status' => Ticket::STATUS_PENDING])
            ->assertHasNoTableBulkActionErrors();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assigned_to' => $agent->id,
            'status' => Ticket::STATUS_PENDING,
        ]);
    }
}
