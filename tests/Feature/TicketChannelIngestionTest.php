<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Contact;
use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\Ticket;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TicketChannelIngestionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @test @group A2-TS-01 */
    public function channel_adapter_can_ingest_message_payloads(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $contact = Contact::factory()->forBrand($brand)->create();
        $ticket = Ticket::factory()->forBrand($brand)->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'contact_id' => $contact->id,
        ]);

        $agent = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $agent->assignRole('Agent');

        Config::set('services.channels.ingestion_secret', 'secret-token');

        $headers = [
            config('tenancy.tenant_header') => $tenant->id,
            config('tenancy.brand_header') => $brand->id,
            'X-Channel-Token' => 'secret-token',
        ];

        $payload = [
            'body' => 'Inbound email reply',
            'channel' => 'email',
            'visibility' => 'internal',
            'author_type' => 'user',
            'author_id' => $agent->id,
            'external_id' => 'email-456',
            'participants' => [
                [
                    'participant_type' => 'user',
                    'participant_id' => $agent->id,
                    'role' => 'agent',
                    'visibility' => 'internal',
                ],
                [
                    'participant_type' => 'contact',
                    'participant_id' => $contact->id,
                    'role' => 'requester',
                    'visibility' => 'external',
                ],
            ],
            'attachments' => [
                [
                    'disk' => 'local',
                    'path' => 'attachments/transcript.txt',
                    'filename' => 'transcript.txt',
                    'mime_type' => 'text/plain',
                    'size' => 24,
                ],
            ],
        ];

        $this->actingAs($agent);

        $response = $this->withHeaders($headers)
            ->postJson("/api/tickets/{$ticket->id}/ingest", $payload);

        $response->assertCreated();
        $messageId = $response->json('data.id');

        $this->assertDatabaseHas('ticket_messages', [
            'id' => $messageId,
            'tenant_id' => $tenant->id,
            'visibility' => 'internal',
        ]);

        $this->assertDatabaseHas('ticket_participants', [
            'ticket_id' => $ticket->id,
            'participant_type' => 'user',
            'participant_id' => $agent->id,
            'visibility' => 'internal',
        ]);

        $this->assertDatabaseMissing('ticket_participants', [
            'ticket_id' => $ticket->id,
            'participant_type' => 'contact',
            'participant_id' => $contact->id,
            'visibility' => 'internal',
        ]);

        $this->assertDatabaseHas('attachments', [
            'attachable_id' => $messageId,
            'filename' => 'transcript.txt',
        ]);
    }

    /** @test @group A2-TS-01 */
    public function ingestion_fails_with_invalid_token(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $contact = Contact::factory()->forBrand($brand)->create();
        $ticket = Ticket::factory()->forBrand($brand)->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'contact_id' => $contact->id,
        ]);

        $agent = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $agent->assignRole('Agent');

        Config::set('services.channels.ingestion_secret', 'expected-secret');

        $this->actingAs($agent);

        $headers = [
            config('tenancy.tenant_header') => $tenant->id,
            config('tenancy.brand_header') => $brand->id,
            'X-Channel-Token' => 'wrong-secret',
        ];

        $this->withHeaders($headers)
            ->postJson("/api/tickets/{$ticket->id}/ingest", [
                'body' => 'Unauthorized note',
                'channel' => 'email',
            ])
            ->assertStatus(401);
    }
}
