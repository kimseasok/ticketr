<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Company;
use App\Modules\Helpdesk\Models\Contact;
use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_crud_operations(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $company = Company::factory()->forBrand($brand)->create();
        $contact = Contact::factory()->forCompany($company)->create();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);

        $ticket = Ticket::factory()
            ->forContact($contact)
            ->createdBy($user)
            ->assignedTo($user)
            ->create([
                'subject' => 'Initial subject',
            ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'subject' => 'Initial subject',
        ]);

        $ticket->update([
            'status' => Ticket::STATUS_PENDING,
            'subject' => 'Updated subject',
        ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => Ticket::STATUS_PENDING,
            'subject' => 'Updated subject',
        ]);

        $ticketId = $ticket->id;
        $ticket->delete();

        $this->assertDatabaseMissing('tickets', [
            'id' => $ticketId,
        ]);
    }
}
