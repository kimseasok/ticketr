<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_inbound_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('mailbox_id')->constrained('email_mailboxes')->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ticket_message_id')->nullable()->constrained('ticket_messages')->nullOnDelete();
            $table->string('message_id');
            $table->string('thread_id')->nullable();
            $table->string('subject')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_email');
            $table->json('to_recipients')->nullable();
            $table->json('cc_recipients')->nullable();
            $table->json('bcc_recipients')->nullable();
            $table->longText('text_body')->nullable();
            $table->longText('html_body')->nullable();
            $table->unsignedInteger('attachments_count')->default(0);
            $table->enum('status', ['pending', 'processed', 'failed'])->default('pending');
            $table->timestampTz('received_at');
            $table->timestampTz('processed_at')->nullable();
            $table->json('error_info')->nullable();
            $table->json('headers')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'message_id']);
            $table->index(['tenant_id', 'mailbox_id', 'status']);
            $table->index(['tenant_id', 'thread_id']);
            $table->index(['tenant_id', 'from_email']);
            $table->index(['tenant_id', 'ticket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_inbound_messages');
    }
};
