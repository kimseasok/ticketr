<?php

namespace App\Modules\Helpdesk\Events;

use App\Modules\Helpdesk\Models\Ticket;

class TicketStatusChanged
{
    public function __construct(
        public readonly Ticket $ticket,
        public readonly string $previousStatus,
        public readonly string $currentStatus
    ) {
    }
}
