<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('automation_rule_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->string('trigger_event');
            $table->enum('status', ['matched', 'skipped', 'failed']);
            $table->string('result')->nullable();
            $table->json('context')->nullable();
            $table->timestampTz('executed_at');
            $table->timestamps();

            $table->index(['tenant_id', 'trigger_event']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'ticket_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_rule_executions');
    }
};
