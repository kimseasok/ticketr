<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_mailboxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->enum('direction', ['inbound', 'outbound', 'bidirectional'])->default('bidirectional');
            $table->string('protocol');
            $table->string('host');
            $table->unsignedInteger('port');
            $table->string('encryption')->nullable();
            $table->string('username');
            $table->text('credentials');
            $table->json('settings')->nullable();
            $table->json('sync_state')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampTz('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
            $table->index(['tenant_id', 'brand_id']);
            $table->index(['tenant_id', 'direction']);
            $table->index(['tenant_id', 'protocol']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_mailboxes');
    }
};
