<?php

namespace App\Modules\Helpdesk\Events;

use App\Modules\Helpdesk\Models\Ticket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketSlaBreached
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly string $metric,
        public readonly ?string $breachedAt
    ) {
    }
}
