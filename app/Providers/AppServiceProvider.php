<?php

namespace App\Providers;

use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Observers\TicketObserver;
use App\Modules\Helpdesk\Observers\TicketSlaObserver;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(TenantContext::class, function () {
            return new TenantContext();
        });

        $this->app->alias(TenantContext::class, 'tenant.context');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Factory::guessFactoryNamesUsing(fn (string $modelName) => 'Database\\Factories\\'.class_basename($modelName).'Factory');

        Ticket::observe([TicketObserver::class, TicketSlaObserver::class]);
    }
}
