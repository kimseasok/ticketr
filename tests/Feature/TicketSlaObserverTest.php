<?php

namespace Tests\Feature;

use App\Modules\Helpdesk\Events\TicketSlaBreached;
use App\Modules\Helpdesk\Events\TicketSlaRecovered;
use App\Modules\Helpdesk\Models\AuditLog;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\Ticket;
use Database\Seeders\TicketLifecycleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TicketSlaObserverTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @group A1-OB-01
     */
    public function test_sla_breach_event_fires_when_due_passes(): void
    {
        Event::fake([TicketSlaBreached::class]);

        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        app(TicketLifecycleSeeder::class)->runForTenant($tenant->id);

        $baseTime = now();
        $this->travelTo($baseTime);

        $ticket = Ticket::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'first_response_due_at' => $baseTime->copy()->addMinutes(5),
            'resolution_due_at' => $baseTime->copy()->addHours(2),
        ]);

        $this->travelTo($baseTime->copy()->addMinutes(10));

        $ticket->refresh()->update(['description' => 'Time elapsed']);

        Event::assertDispatched(TicketSlaBreached::class, function (TicketSlaBreached $event) use ($ticket) {
            return $event->ticket->is($ticket) && $event->metric === 'first_response';
        });

        $this->travelBack();
    }

    /**
     * @group A1-OB-01
     */
    public function test_sla_recovery_event_persists_audit_metadata(): void
    {
        Event::fake([TicketSlaRecovered::class]);

        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        app(TicketLifecycleSeeder::class)->runForTenant($tenant->id);

        $baseTime = now();
        $this->travelTo($baseTime);

        $ticket = Ticket::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'first_response_due_at' => $baseTime->copy()->subMinutes(10),
            'resolution_due_at' => $baseTime->copy()->addHour(),
        ]);

        $this->travelTo($baseTime->copy()->addMinutes(20));

        $ticket->update([
            'first_response_due_at' => now()->addMinutes(15),
            'first_responded_at' => now()->subMinutes(5),
        ]);

        $ticket->refresh();

        Event::assertDispatched(TicketSlaRecovered::class, function (TicketSlaRecovered $event) use ($ticket) {
            return $event->ticket->is($ticket) && $event->metric === 'first_response';
        });

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'auditable_id' => $ticket->id,
            'action' => 'ticket.updated',
        ]);

        $audit = AuditLog::latest()->first();
        $this->assertEquals(['sla' => $ticket->safeMetadata()['sla']], $audit->new_values['metadata'] ?? []);

        $this->travelBack();
    }

    /**
     * @group A1-OB-01
     */
    public function test_bulk_updates_only_dispatch_expected_sla_events(): void
    {
        Event::fake([TicketSlaBreached::class]);

        $tenant = Tenant::factory()->create();
        app(TicketLifecycleSeeder::class)->runForTenant($tenant->id);

        $baseTime = now();
        $this->travelTo($baseTime);

        $breachingTickets = Ticket::factory()->count(5)->create([
            'tenant_id' => $tenant->id,
            'first_response_due_at' => $baseTime->copy()->addMinutes(10),
            'resolution_due_at' => $baseTime->copy()->addHours(2),
        ]);

        $pendingTickets = Ticket::factory()->count(5)->create([
            'tenant_id' => $tenant->id,
            'first_response_due_at' => $baseTime->copy()->addMinutes(120),
            'resolution_due_at' => $baseTime->copy()->addHours(4),
        ]);

        $this->travelTo($baseTime->copy()->addMinutes(30));

        $breachingTickets->each(fn (Ticket $ticket) => $ticket->update(['description' => 'breach']));
        $pendingTickets->each(fn (Ticket $ticket) => $ticket->update(['description' => 'pending']));

        Event::assertDispatchedTimes(TicketSlaBreached::class, 5);

        $this->travelBack();
    }
}
