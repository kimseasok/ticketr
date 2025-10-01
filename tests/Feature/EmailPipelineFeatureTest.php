<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Helpdesk\Filament\Resources\EmailMailboxResource\Pages\CreateEmailMailbox;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\EmailInboundMessage;
use App\Modules\Helpdesk\Models\EmailMailbox;
use App\Modules\Helpdesk\Models\EmailOutboundMessage;
use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Services\Email\Contracts\MailboxDeliverer;
use App\Modules\Helpdesk\Services\Email\Contracts\MailboxFetcher;
use App\Modules\Helpdesk\Services\Email\Data\InboundEmailMessage;
use App\Modules\Helpdesk\Services\Email\MailboxConnectorRegistry;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class EmailPipelineFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Storage::fake('local');
    }

    /**
     * @group TKT-EMAIL-MD-02
     */
    public function test_mailbox_api_sync_and_delivery_flow(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $admin->assignRole('Admin');

        $headers = $this->headersFor($tenant->id, $brand->id);

        $registry = app(MailboxConnectorRegistry::class);
        $registry->registerFetcher('imap', fn () => new class implements MailboxFetcher {
            public function fetch(EmailMailbox $mailbox): array
            {
                return [
                    new InboundEmailMessage(
                        messageId: 'unit-test-message',
                        threadId: null,
                        subject: 'Customer Support Request',
                        fromEmail: 'customer@example.test',
                        fromName: 'Customer Example',
                        to: [$mailbox->username],
                        cc: [],
                        bcc: [],
                        textBody: 'Please help me with my account.',
                        htmlBody: '<p>Please help me with my account.</p>',
                        attachments: [
                            [
                                'filename' => 'note.txt',
                                'content' => base64_encode('Attachment body'),
                                'encoding' => 'base64',
                                'mime_type' => 'text/plain',
                            ],
                        ],
                        headers: ['message-id' => 'unit-test-message'],
                    ),
                ];
            }
        });
        $registry->registerDeliverer('imap', fn () => new class implements MailboxDeliverer {
            public function deliver(EmailOutboundMessage $message): \App\Modules\Helpdesk\Services\Email\Data\DeliveryResult
            {
                return new \App\Modules\Helpdesk\Services\Email\Data\DeliveryResult(true, providerMessageId: (string) Str::uuid());
            }
        });

        $this->actingAs($admin)
            ->withHeaders($headers)
            ->postJson('/api/email/mailboxes', [
                'name' => 'Support Mailbox',
                'slug' => 'support-mailbox',
                'direction' => 'bidirectional',
                'protocol' => 'imap',
                'host' => 'imap.example.test',
                'port' => 993,
                'encryption' => 'ssl',
                'username' => 'support@example.test',
                'credentials' => ['password' => 'secret'],
                'settings' => ['folder' => 'INBOX', 'mailer' => 'smtp'],
                'brand_id' => $brand->id,
            ])->assertCreated();

        $mailbox = EmailMailbox::first();

        $this->actingAs($admin)
            ->withHeaders($headers)
            ->postJson("/api/email/mailboxes/{$mailbox->id}/sync")
            ->assertOk()
            ->assertJson(['fetched' => 1, 'processed' => 1]);

        $inbound = EmailInboundMessage::first();
        $this->assertNotNull($inbound);
        $this->assertSame('processed', $inbound->status);
        $this->assertNotNull($inbound->ticket_id);
        $this->assertDatabaseHas('email_attachments', [
            'message_type' => EmailInboundMessage::class,
            'message_id' => $inbound->id,
        ]);

        $ticket = Ticket::find($inbound->ticket_id);
        $this->assertNotNull($ticket);

        $agent = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $agent->assignRole('Agent');

        Mail::fake();

        $this->actingAs($agent)
            ->withHeaders($headers)
            ->postJson("/api/tickets/{$ticket->id}/messages", [
                'body' => 'Thanks for reaching out, we are on it.',
                'author_type' => 'user',
                'author_id' => $agent->id,
                'channel' => 'email',
                'email' => [
                    'subject' => 'Re: Customer Support Request',
                    'mailbox_id' => $mailbox->id,
                    'to' => ['customer@example.test'],
                    'text_body' => 'Thanks for reaching out, we are on it.',
                ],
            ])->assertCreated();

        $outbound = EmailOutboundMessage::first();
        $this->assertNotNull($outbound);
        $this->assertSame('queued', $outbound->status);

        $this->actingAs($agent)
            ->withHeaders($headers)
            ->postJson("/api/email/outbound-messages/{$outbound->id}/deliver")
            ->assertOk()
            ->assertJson(['status' => 'sent']);

        $this->assertEquals('sent', $outbound->fresh()->status);
        $this->assertNotNull($outbound->fresh()->provider_message_id);
    }

    /**
     * @group TKT-EMAIL-MD-02
     */
    public function test_filament_mailbox_creation(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $admin->assignRole('Admin');

        app(TenantContext::class)->setTenantId($tenant->id);
        app(TenantContext::class)->setBrandId($brand->id);

        Livewire::actingAs($admin)
            ->test(CreateEmailMailbox::class)
            ->set('data', [
                'name' => 'Ops Mailbox',
                'slug' => 'ops-mailbox',
                'direction' => 'outbound',
                'protocol' => 'smtp',
                'host' => 'smtp.ops.test',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'ops@example.test',
                'credentials' => ['password' => 'ops-secret'],
                'settings' => ['mailer' => 'smtp'],
                'brand_id' => $brand->id,
                'is_active' => true,
            ])
            ->call('create');

        $this->assertDatabaseHas('email_mailboxes', [
            'slug' => 'ops-mailbox',
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * @group TKT-EMAIL-RB-03
     */
    public function test_agent_cannot_manage_mailboxes(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $agent = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $agent->assignRole('Agent');

        $headers = $this->headersFor($tenant->id, $brand->id);

        $this->actingAs($agent)
            ->withHeaders($headers)
            ->postJson('/api/email/mailboxes', [
                'name' => 'Restricted Mailbox',
                'slug' => 'restricted-mailbox',
                'direction' => 'inbound',
                'protocol' => 'imap',
                'host' => 'imap.restricted.test',
                'port' => 993,
                'encryption' => 'ssl',
                'username' => 'restricted@example.test',
                'credentials' => ['password' => 'secret'],
            ])
            ->assertForbidden();
    }

    /**
     * @group TKT-EMAIL-RB-03
     */
    public function test_viewer_can_list_messages_but_cannot_deliver(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $viewer = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $viewer->assignRole('Viewer');

        $mailbox = EmailMailbox::factory()->forBrand($brand)->create([
            'tenant_id' => $tenant->id,
            'username' => 'viewer@example.test',
        ]);

        $inbound = EmailInboundMessage::factory()->forMailbox($mailbox)->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'status' => 'processed',
        ]);

        $outbound = EmailOutboundMessage::factory()->forMailbox($mailbox)->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'status' => 'queued',
        ]);

        $headers = $this->headersFor($tenant->id, $brand->id);

        $this->actingAs($viewer)
            ->withHeaders($headers)
            ->getJson('/api/email/inbound-messages')
            ->assertOk()
            ->assertJsonFragment(['id' => $inbound->id]);

        $this->actingAs($viewer)
            ->withHeaders($headers)
            ->postJson("/api/email/outbound-messages/{$outbound->id}/deliver")
            ->assertForbidden();
    }

    private function headersFor(int $tenantId, int $brandId): array
    {
        return [
            config('tenancy.tenant_header') => $tenantId,
            config('tenancy.brand_header') => $brandId,
        ];
    }
}
