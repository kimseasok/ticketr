<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject');
            $table->text('description')->nullable();
            $table->enum('status', ['open', 'pending', 'resolved', 'closed', 'archived'])->default('open');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('channel', ['email', 'web', 'chat', 'phone'])->default('email');
            $table->string('reference');
            $table->json('metadata')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamp('first_response_due_at')->nullable();
            $table->timestamp('resolution_due_at')->nullable();
            $table->timestamp('first_responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('last_customer_reply_at')->nullable();
            $table->timestamp('last_agent_reply_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('brand_id');
            $table->index('contact_id');
            $table->index('status');
            $table->index('priority');
            $table->index('resolution_due_at');
            $table->index('archived_at');
            $table->unique(['tenant_id', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
