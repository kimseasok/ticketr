<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ticket_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('last_message_id')->nullable()->constrained('ticket_messages')->nullOnDelete();
            $table->enum('participant_type', ['user', 'contact', 'system'])->default('contact');
            $table->unsignedBigInteger('participant_id')->nullable();
            $table->enum('role', ['requester', 'cc', 'agent', 'watcher'])->default('requester');
            $table->enum('visibility', ['external', 'internal'])->default('external');
            $table->timestampTz('last_seen_at')->nullable();
            $table->timestampTz('last_typing_at')->nullable();
            $table->boolean('is_muted')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'ticket_id', 'participant_type', 'participant_id']);
            $table->index(['tenant_id', 'brand_id']);
            $table->index(['tenant_id', 'role']);
            $table->index(['tenant_id', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_participants');
    }
};
