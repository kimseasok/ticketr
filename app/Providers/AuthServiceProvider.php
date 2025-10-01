<?php

namespace App\Providers;

use App\Modules\Helpdesk\Models\KnowledgeBaseArticle;
use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Models\TicketMessage;
use App\Modules\Helpdesk\Models\Contact;
use App\Policies\ContactPolicy;
use App\Policies\KnowledgeBaseArticlePolicy;
use App\Policies\TicketMessagePolicy;
use App\Policies\TicketPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Ticket::class => TicketPolicy::class,
        TicketMessage::class => TicketMessagePolicy::class,
        Contact::class => ContactPolicy::class,
        KnowledgeBaseArticle::class => KnowledgeBaseArticlePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
