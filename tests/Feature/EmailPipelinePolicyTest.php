<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\EmailInboundMessage;
use App\Modules\Helpdesk\Models\EmailMailbox;
use App\Modules\Helpdesk\Models\EmailOutboundMessage;
use App\Modules\Helpdesk\Models\Tenant;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class EmailPipelinePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function makeMailboxGraph(): array
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $mailbox = EmailMailbox::factory()->forBrand($brand)->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'protocol' => 'imap',
            'direction' => 'bidirectional',
            'credentials' => ['password' => 'secret'],
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

        return [$tenant, $brand, $mailbox, $inbound, $outbound];
    }

    /**
     * @group TKT-EMAIL-RB-03
     */
    public function test_admin_has_full_email_pipeline_access(): void
    {
        [$tenant, $brand, $mailbox, $inbound, $outbound] = $this->makeMailboxGraph();

        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $admin->assignRole('Admin');

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', EmailMailbox::class));
        $this->assertTrue(Gate::forUser($admin)->allows('create', EmailMailbox::class));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $mailbox));
        $this->assertTrue(Gate::forUser($admin)->allows('sync', $mailbox));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $inbound));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $outbound));
        $this->assertTrue(Gate::forUser($admin)->allows('deliver', $outbound));
    }

    /**
     * @group TKT-EMAIL-RB-03
     */
    public function test_agent_can_view_messages_and_deliver_but_cannot_manage_mailboxes(): void
    {
        [$tenant, $brand, $mailbox, $inbound, $outbound] = $this->makeMailboxGraph();

        $agent = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $agent->assignRole('Agent');

        $this->assertTrue(Gate::forUser($agent)->allows('viewAny', EmailInboundMessage::class));
        $this->assertTrue(Gate::forUser($agent)->allows('view', $inbound));
        $this->assertTrue(Gate::forUser($agent)->allows('view', $outbound));
        $this->assertTrue(Gate::forUser($agent)->allows('deliver', $outbound));
        $this->assertFalse(Gate::forUser($agent)->allows('create', EmailMailbox::class));
        $this->assertFalse(Gate::forUser($agent)->allows('update', $mailbox));
        $this->assertFalse(Gate::forUser($agent)->allows('sync', $mailbox));
    }

    /**
     * @group TKT-EMAIL-RB-03
     */
    public function test_viewer_has_read_only_access_to_email_messages(): void
    {
        [$tenant, $brand, $mailbox, $inbound, $outbound] = $this->makeMailboxGraph();

        $viewer = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $viewer->assignRole('Viewer');

        $this->assertTrue(Gate::forUser($viewer)->allows('viewAny', EmailInboundMessage::class));
        $this->assertTrue(Gate::forUser($viewer)->allows('view', $inbound));
        $this->assertTrue(Gate::forUser($viewer)->allows('view', $outbound));
        $this->assertFalse(Gate::forUser($viewer)->allows('deliver', $outbound));
        $this->assertFalse(Gate::forUser($viewer)->allows('sync', $mailbox));
        $this->assertFalse(Gate::forUser($viewer)->allows('view', $mailbox));
    }
}
