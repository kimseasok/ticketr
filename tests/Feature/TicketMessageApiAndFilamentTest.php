<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Helpdesk\Filament\Resources\TicketMessageResource\Pages\CreateTicketMessage;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Contact;
use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Models\TicketMessage;
use App\Modules\Helpdesk\Services\TicketMessageService;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TicketMessageApiAndFilamentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @test @group A2-MD-01 */
    public function agent_can_append_messages_via_api_with_dedupe(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $contact = Contact::factory()->forBrand($brand)->create();
        $ticket = Ticket::factory()->forBrand($brand)->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'contact_id' => $contact->id,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $user->assignRole('Agent');

        $this->actingAs($user);

        $headers = [
            config('tenancy.tenant_header') => $tenant->id,
            config('tenancy.brand_header') => $brand->id,
        ];

        $payload = [
            'body' => 'This is a new collaboration message.',
            'visibility' => 'internal',
            'channel' => 'email',
            'author_type' => 'user',
            'author_id' => $user->id,
            'external_id' => 'email-123',
            'participants' => [
                [
                    'participant_type' => 'user',
                    'participant_id' => $user->id,
                    'role' => 'agent',
                    'visibility' => 'internal',
                ],
            ],
            'attachments' => [
                [
                    'disk' => 'local',
                    'path' => 'attachments/demo.txt',
                    'filename' => 'demo.txt',
                    'mime_type' => 'text/plain',
                    'size' => 12,
                ],
            ],
        ];

        $response = $this->withHeaders($headers)->postJson("/api/tickets/{$ticket->id}/messages", $payload);
        $response->assertCreated();
        $messageId = $response->json('data.id');

        $this->assertDatabaseHas('ticket_messages', [
            'id' => $messageId,
            'tenant_id' => $tenant->id,
            'ticket_id' => $ticket->id,
            'external_id' => 'email-123',
        ]);

        $this->withHeaders($headers)
            ->getJson("/api/tickets/{$ticket->id}/messages")
            ->assertOk()
            ->assertJsonFragment(['id' => $messageId]);

        $duplicate = $this->withHeaders($headers)->postJson("/api/tickets/{$ticket->id}/messages", $payload);
        $duplicate->assertCreated();
        $this->assertEquals($messageId, $duplicate->json('data.id'));

        $this->assertDatabaseCount('ticket_messages', 1);
        $this->assertDatabaseHas('ticket_participants', [
            'tenant_id' => $tenant->id,
            'ticket_id' => $ticket->id,
            'participant_id' => $user->id,
        ]);

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => TicketMessage::class,
            'attachable_id' => $messageId,
            'filename' => 'demo.txt',
        ]);

        $this->assertEquals(1, TicketMessage::find($messageId)->attachments_count);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ticket_message.created',
            'auditable_type' => TicketMessage::class,
            'auditable_id' => $messageId,
        ]);
    }

    /** @test @group A2-MD-01 */
    public function filament_agent_can_create_ticket_message_with_scope(): void
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
        $service->append($ticket, [
            'body' => 'Existing message',
            'author_type' => 'contact',
            'author_id' => $contact->id,
            'channel' => 'email',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $user->assignRole('Agent');

        app(TenantContext::class)->setTenantId($tenant->id);
        app(TenantContext::class)->setBrandId($brand->id);

        Livewire::actingAs($user)
            ->test(CreateTicketMessage::class)
            ->set('data', [
                'tenant_id' => $tenant->id,
                'brand_id' => $brand->id,
                'ticket_id' => $ticket->id,
                'visibility' => 'public',
                'channel' => 'web',
                'body' => 'Filament created message',
                'metadata' => ['source' => 'ui'],
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ticket_messages', [
            'body' => 'Filament created message',
            'tenant_id' => $tenant->id,
        ]);

        Livewire::actingAs($user)
            ->test(CreateTicketMessage::class)
            ->set('data', [
                'tenant_id' => $tenant->id,
                'brand_id' => $brand->id,
                'ticket_id' => $ticket->id,
                'visibility' => 'public',
                'channel' => 'web',
                'body' => '',
            ])
            ->call('create')
            ->assertHasErrors(['data.body' => 'required']);
    }

    /** @test @group A2-MD-01 */
    public function viewer_cannot_create_ticket_message_via_api(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $contact = Contact::factory()->forBrand($brand)->create();
        $ticket = Ticket::factory()->forBrand($brand)->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'contact_id' => $contact->id,
        ]);

        $viewer = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $viewer->assignRole('Viewer');

        $this->actingAs($viewer);

        $headers = [
            config('tenancy.tenant_header') => $tenant->id,
            config('tenancy.brand_header') => $brand->id,
        ];

        $this->withHeaders($headers)
            ->postJson("/api/tickets/{$ticket->id}/messages", [
                'body' => 'Forbidden message',
            ])
            ->assertForbidden();
    }
}
