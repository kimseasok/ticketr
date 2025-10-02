<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            TenantSeeder::class,
            RoleSeeder::class,
            TicketLifecycleSeeder::class,
            MonitoringTokenSeeder::class,
            ChannelAdapterMacroSeeder::class,
            AutomationSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
