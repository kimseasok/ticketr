<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ticket_workflow_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_status_id')->constrained('ticket_statuses')->cascadeOnDelete();
            $table->foreignId('to_status_id')->constrained('ticket_statuses')->cascadeOnDelete();
            $table->boolean('requires_comment')->default(false);
            $table->boolean('requires_resolution_note')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'from_status_id', 'to_status_id'], 'ticket_transition_unique');
            $table->index(['tenant_id', 'from_status_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_workflow_transitions');
    }
};
