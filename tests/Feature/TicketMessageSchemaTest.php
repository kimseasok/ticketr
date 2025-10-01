<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TicketMessageSchemaTest extends TestCase
{
    use RefreshDatabase;

    /** @test @group A2-DB-01 */
    public function ticket_message_schema_supports_tenant_scoped_collaboration(): void
    {
        $this->assertTrue(Schema::hasTable('ticket_messages'));
        $this->assertTrue(Schema::hasColumns('ticket_messages', [
            'tenant_id',
            'brand_id',
            'ticket_id',
            'author_type',
            'visibility',
            'channel',
            'external_id',
            'dedupe_hash',
            'attachments_count',
            'body',
            'metadata',
            'posted_at',
        ]));

        $this->assertTrue(Schema::hasTable('ticket_participants'));
        $this->assertTrue(Schema::hasColumns('ticket_participants', [
            'tenant_id',
            'brand_id',
            'ticket_id',
            'last_message_id',
            'participant_type',
            'participant_id',
            'role',
            'visibility',
            'last_seen_at',
            'last_typing_at',
            'is_muted',
        ]));
    }
}
