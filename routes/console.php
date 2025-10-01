<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Database\Seeders\TicketLifecycleSeeder;
use App\Modules\Helpdesk\Models\Tenant;
use Illuminate\Console\Command;

Artisan::command('tickets:seed-defaults {tenant?}', function (?string $tenant = null) {
    /** @var TicketLifecycleSeeder $seeder */
    $seeder = app(TicketLifecycleSeeder::class);

    if ($tenant) {
        $tenantModel = Tenant::query()
            ->where('slug', $tenant)
            ->orWhere('id', (int) $tenant)
            ->first();

        if (! $tenantModel) {
            $this->error("Tenant '{$tenant}' not found.");

            return Command::FAILURE;
        }

        $seeder->runForTenant($tenantModel->id);
        $this->info("Seeded ticket lifecycle defaults for tenant '{$tenantModel->slug}'.");

        return Command::SUCCESS;
    }

    $seeder->run();
    $this->info('Seeded ticket lifecycle defaults for all tenants.');

    return Command::SUCCESS;
})->purpose('Seed default ticket statuses, priorities, and workflows per tenant');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
