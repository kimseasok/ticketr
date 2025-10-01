<?php

namespace App\Providers;

use App\Modules\Helpdesk\Models\EmailMailbox;
use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Models\TicketMessage;
use App\Modules\Helpdesk\Services\Email\Connectors\ImapMailboxFetcher;
use App\Modules\Helpdesk\Services\Email\Connectors\SmtpMailboxDeliverer;
use App\Modules\Helpdesk\Services\Email\MailboxConnectorRegistry;
use App\Modules\Helpdesk\Observers\TicketObserver;
use App\Modules\Helpdesk\Observers\TicketSlaObserver;
use App\Modules\Helpdesk\Observers\TicketMessageObserver;
use App\Modules\Helpdesk\Observers\TicketMessageEmailObserver;
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

        $this->app->singleton(MailboxConnectorRegistry::class, function () {
            $registry = new MailboxConnectorRegistry();
            $registry->registerFetcher('imap', fn () => new ImapMailboxFetcher());
            $registry->registerDeliverer('smtp', fn (EmailMailbox $mailbox) => new SmtpMailboxDeliverer($mailbox));

            return $registry;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Factory::guessFactoryNamesUsing(fn (string $modelName) => 'Database\\Factories\\'.class_basename($modelName).'Factory');

        Ticket::observe([TicketObserver::class, TicketSlaObserver::class]);
        TicketMessage::observe([
            TicketMessageObserver::class,
            TicketMessageEmailObserver::class,
        ]);
    }
}
