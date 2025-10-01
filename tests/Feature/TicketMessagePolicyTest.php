<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Contact;
use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Models\TicketMessage;
use App\Modules\Helpdesk\Services\TicketMessageService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TicketMessagePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @test @group A2-MD-01 */
    public function policy_enforces_role_matrix_for_ticket_messages(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $contact = Contact::factory()->forBrand($brand)->create();
        $ticket = Ticket::factory()->forBrand($brand)->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'contact_id' => $contact->id,
        ]);

        $service = app(TicketMessageService::class);
        $message = $service->append($ticket, [
            'body' => 'Policy message',
            'author_type' => 'contact',
            'author_id' => $contact->id,
        ]);

        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'brand_id' => $brand->id]);
        $admin->assignRole('Admin');

        $agent = User::factory()->create(['tenant_id' => $tenant->id, 'brand_id' => $brand->id]);
        $agent->assignRole('Agent');

        $viewer = User::factory()->create(['tenant_id' => $tenant->id, 'brand_id' => $brand->id]);
        $viewer->assignRole('Viewer');

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', [TicketMessage::class, $ticket]));
        $this->assertTrue(Gate::forUser($admin)->allows('create', [TicketMessage::class, $ticket]));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $message));

        $this->assertTrue(Gate::forUser($agent)->allows('viewAny', [TicketMessage::class, $ticket]));
        $this->assertTrue(Gate::forUser($agent)->allows('create', [TicketMessage::class, $ticket]));
        $this->assertTrue(Gate::forUser($agent)->allows('update', $message));

        $this->assertTrue(Gate::forUser($viewer)->allows('viewAny', [TicketMessage::class, $ticket]));
        $this->assertFalse(Gate::forUser($viewer)->allows('create', [TicketMessage::class, $ticket]));
        $this->assertFalse(Gate::forUser($viewer)->allows('update', $message));
    }
}
