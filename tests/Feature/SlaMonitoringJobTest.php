<?php

namespace Tests\Feature;

use App\Jobs\ProcessTicketSla;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\SlaPolicy;
use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Services\SlaPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlaMonitoringJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @group TKT-AUT-OB-05
     */
    public function test_job_creates_transitions_and_alerts_for_breaches(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $policy = SlaPolicy::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'first_response_minutes' => 30,
            'resolution_minutes' => 60,
            'grace_minutes' => 5,
            'alert_after_minutes' => 10,
        ]);

        $ticket = Ticket::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'created_at' => now()->subHours(5),
            'status' => Ticket::STATUS_OPEN,
        ]);

        /** @var SlaPolicyService $slaService */
        $slaService = app(SlaPolicyService::class);
        $slaService->assignPolicy($ticket, $policy);
        $ticket->forceFill([
            'first_response_due_at' => now()->subMinutes(20),
            'resolution_due_at' => now()->subMinutes(5),
            'sla_snapshot' => null,
        ])->save();

        ProcessTicketSla::dispatchSync($ticket->id);

        $ticket->refresh();

        $this->assertSame('breached', data_get($ticket->sla_snapshot, 'first_response.state'));
        $this->assertDatabaseHas('sla_transitions', [
            'ticket_id' => $ticket->id,
            'metric' => 'first_response',
            'to_state' => 'breached',
        ]);
        $this->assertNotNull($ticket->next_sla_check_at);
    }

    /**
     * @group TKT-AUT-OB-05
     */
    public function test_job_handles_missing_ticket_gracefully(): void
    {
        ProcessTicketSla::dispatchSync(9999);

        $this->assertDatabaseCount('sla_transitions', 0);
    }
}
