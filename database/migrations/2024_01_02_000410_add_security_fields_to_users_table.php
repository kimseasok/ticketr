<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('two_factor_secret')->nullable()->after('remember_token');
            $table->json('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->json('ip_allowlist')->nullable()->after('two_factor_confirmed_at');
            $table->json('ip_blocklist')->nullable()->after('ip_allowlist');
            $table->string('last_login_ip')->nullable()->after('ip_blocklist');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'ip_allowlist',
                'ip_blocklist',
                'last_login_ip',
            ]);
        });
    }
};
