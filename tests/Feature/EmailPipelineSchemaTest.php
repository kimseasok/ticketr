<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmailPipelineSchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @group TKT-EMAIL-DB-01
     */
    public function test_email_pipeline_tables_and_columns_exist(): void
    {
        $this->assertTrue(Schema::hasTable('email_mailboxes'));
        $this->assertTrue(Schema::hasColumns('email_mailboxes', [
            'id', 'tenant_id', 'name', 'slug', 'direction', 'protocol', 'host', 'port', 'username', 'credentials',
        ]));

        $this->assertTrue(Schema::hasTable('email_inbound_messages'));
        $this->assertTrue(Schema::hasColumns('email_inbound_messages', [
            'message_id', 'mailbox_id', 'ticket_id', 'status', 'received_at', 'processed_at',
        ]));

        $this->assertTrue(Schema::hasTable('email_outbound_messages'));
        $this->assertTrue(Schema::hasColumns('email_outbound_messages', [
            'mailbox_id', 'ticket_id', 'status', 'attempts', 'scheduled_at', 'sent_at',
        ]));

        $this->assertTrue(Schema::hasTable('email_attachments'));
        $this->assertTrue(Schema::hasColumns('email_attachments', [
            'message_type', 'message_id', 'filename', 'mime_type', 'checksum',
        ]));
    }
}
