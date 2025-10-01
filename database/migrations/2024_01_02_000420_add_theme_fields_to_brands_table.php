<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table): void {
            $table->string('primary_color')->nullable()->after('metadata');
            $table->string('secondary_color')->nullable()->after('primary_color');
            $table->string('accent_color')->nullable()->after('secondary_color');
            $table->string('logo_url')->nullable()->after('accent_color');
            $table->string('portal_domain')->nullable()->after('logo_url');
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table): void {
            $table->dropColumn([
                'primary_color',
                'secondary_color',
                'accent_color',
                'logo_url',
                'portal_domain',
            ]);
        });
    }
};
