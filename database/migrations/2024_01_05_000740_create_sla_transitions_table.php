<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sla_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sla_policy_id')->nullable()->constrained('sla_policies')->nullOnDelete();
            $table->enum('metric', ['first_response', 'resolution']);
            $table->string('from_state');
            $table->string('to_state');
            $table->timestampTz('transitioned_at');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'metric']);
            $table->index(['tenant_id', 'ticket_id']);
            $table->index(['tenant_id', 'transitioned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_transitions');
    }
};
