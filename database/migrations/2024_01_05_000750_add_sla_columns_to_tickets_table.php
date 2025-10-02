<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('sla_policy_id')->nullable()->after('priority')->constrained('sla_policies')->nullOnDelete();
            $table->json('sla_snapshot')->nullable()->after('metadata');
            $table->timestampTz('next_sla_check_at')->nullable()->after('sla_snapshot');

            $table->index(['tenant_id', 'sla_policy_id']);
            $table->index(['tenant_id', 'next_sla_check_at']);
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('tickets_tenant_id_sla_policy_id_index');
            $table->dropIndex('tickets_tenant_id_next_sla_check_at_index');
            $table->dropConstrainedForeignId('sla_policy_id');
            $table->dropColumn(['sla_snapshot', 'next_sla_check_at']);
        });
    }
};
