<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->enum('author_type', ['user', 'contact', 'system'])->default('system');
            $table->unsignedBigInteger('author_id')->nullable();
            $table->enum('visibility', ['public', 'internal'])->default('public');
            $table->string('channel')->default('email');
            $table->string('external_id')->nullable();
            $table->string('dedupe_hash')->nullable();
            $table->unsignedInteger('attachments_count')->default(0);
            $table->longText('body');
            $table->json('metadata')->nullable();
            $table->timestampTz('posted_at')->useCurrent();
            $table->timestamps();

            $table->index(['tenant_id', 'ticket_id', 'posted_at']);
            $table->index(['tenant_id', 'brand_id']);
            $table->index(['tenant_id', 'author_type']);
            $table->unique(['tenant_id', 'external_id']);
            $table->unique(['tenant_id', 'dedupe_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
    }
};
