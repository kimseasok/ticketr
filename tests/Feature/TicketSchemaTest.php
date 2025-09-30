<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Company;
use App\Modules\Helpdesk\Models\Contact;
use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\Ticket;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TicketSchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @group A1-DB-01
     */
    public function test_ticket_schema_supports_sla_and_audit_fields(): void
    {
        $this->assertTrue(
            Schema::hasColumns('tickets', [
                'tenant_id',
                'status',
                'priority',
                'channel',
                'status_changed_at',
                'first_response_due_at',
                'resolution_due_at',
                'first_responded_at',
                'resolved_at',
                'closed_at',
                'archived_at',
                'last_activity_at',
                'deleted_at',
            ])
        );

        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $company = Company::factory()->forBrand($brand)->create();
        $contact = Contact::factory()->forCompany($company)->create();
        $agent = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);

        app(TenantContext::class)->setTenantId($tenant->id);
        app(TenantContext::class)->setBrandId($brand->id);

        $ticket = Ticket::factory()
            ->forContact($contact)
            ->createdBy($agent)
            ->assignedTo($agent)
            ->create([
                'first_response_due_at' => now()->addHour(),
                'resolution_due_at' => now()->addHours(4),
            ]);

        $this->assertNotNull($ticket->status_changed_at);
        $this->assertNotNull($ticket->first_response_due_at);
        $this->assertEquals(Ticket::STATUS_OPEN, $ticket->status);

        $ticket->delete();

        $this->assertSoftDeleted('tickets', [
            'id' => $ticket->id,
        ]);
    }
}
