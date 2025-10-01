<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ticket_macros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('description')->nullable();
            $table->text('body');
            $table->enum('visibility', ['tenant', 'brand', 'private'])->default('tenant');
            $table->json('metadata')->nullable();
            $table->boolean('is_shared')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'brand_id']);
            $table->index(['tenant_id', 'visibility']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_macros');
    }
};
