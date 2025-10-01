<?php

namespace Tests\Feature;

use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\TicketStatus;
use App\Modules\Helpdesk\Models\TicketWorkflowTransition;
use Database\Seeders\TicketLifecycleSeeder;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class TicketLifecycleSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @group A1-SD-01
     */
    public function test_seeder_populates_ticket_defaults_per_tenant(): void
    {
        $tenant = Tenant::factory()->create();

        app(TicketLifecycleSeeder::class)->runForTenant($tenant->id);

        $this->assertDatabaseHas('ticket_statuses', [
            'tenant_id' => $tenant->id,
            'slug' => 'open',
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('ticket_priorities', [
            'tenant_id' => $tenant->id,
            'slug' => 'normal',
            'is_default' => true,
        ]);

        $open = TicketStatus::where('tenant_id', $tenant->id)->where('slug', 'open')->firstOrFail();
        $pending = TicketStatus::where('tenant_id', $tenant->id)->where('slug', 'pending')->firstOrFail();

        $this->assertTrue(
            TicketWorkflowTransition::where('tenant_id', $tenant->id)
                ->where('from_status_id', $open->id)
                ->where('to_status_id', $pending->id)
                ->exists()
        );

        $archived = TicketStatus::where('tenant_id', $tenant->id)->where('slug', 'archived')->firstOrFail();

        $orphan = TicketWorkflowTransition::create([
            'tenant_id' => $tenant->id,
            'from_status_id' => $pending->id,
            'to_status_id' => $archived->id,
            'requires_comment' => false,
            'requires_resolution_note' => false,
        ]);

        app(TicketLifecycleSeeder::class)->runForTenant($tenant->id);

        $this->assertDatabaseMissing('ticket_workflow_transitions', [
            'id' => $orphan->id,
        ]);
    }

    /**
     * @group A1-SD-01
     */
    public function test_console_command_supports_scoped_seeding_and_validation(): void
    {
        $tenant = Tenant::factory()->create(['slug' => 'acme']);

        $exitCode = Artisan::call('tickets:seed-defaults', ['tenant' => 'acme']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertDatabaseHas('ticket_statuses', [
            'tenant_id' => $tenant->id,
            'slug' => 'open',
        ]);

        $failureCode = Artisan::call('tickets:seed-defaults', ['tenant' => 'missing-tenant']);
        $this->assertSame(Command::FAILURE, $failureCode);
        $this->assertStringContainsString("Tenant 'missing-tenant' not found.", Artisan::output());
    }
}
