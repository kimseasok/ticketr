<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sla_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('priority_scope')->nullable();
            $table->string('channel_scope')->nullable();
            $table->unsignedInteger('first_response_minutes');
            $table->unsignedInteger('resolution_minutes');
            $table->unsignedInteger('grace_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('escalation_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('alert_after_minutes')->default(30);
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'brand_id']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['tenant_id', 'priority_scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_policies');
    }
};
