<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('automation_rule_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('automation_rule_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('definition');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('published_at')->nullable();
            $table->timestamps();

            $table->unique(['automation_rule_id', 'version']);
            $table->index(['tenant_id', 'automation_rule_id']);
            $table->index(['automation_rule_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_rule_versions');
    }
};
