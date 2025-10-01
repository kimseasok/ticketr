<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('mailbox_id')->nullable()->constrained('email_mailboxes')->nullOnDelete();
            $table->morphs('message');
            $table->foreignId('attachment_id')->nullable()->constrained('attachments')->nullOnDelete();
            $table->string('filename');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('content_id')->nullable();
            $table->string('disposition')->nullable();
            $table->string('checksum')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'mailbox_id']);
            $table->index(['tenant_id', 'message_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_attachments');
    }
};
