<?php

namespace App\Modules\Helpdesk\Observers;

use App\Modules\Helpdesk\Models\TicketMessage;
use App\Modules\Helpdesk\Services\Email\EmailDeliveryService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class TicketMessageEmailObserver
{
    public function __construct(private readonly EmailDeliveryService $delivery)
    {
    }

    public function created(TicketMessage $message): void
    {
        if ($message->channel !== 'email') {
            return;
        }

        if ($message->author_type !== 'user') {
            return;
        }

        $metadata = $message->metadata ?? [];
        $emailMeta = Arr::get($metadata, 'email', []);

        if (empty($emailMeta['to'])) {
            return;
        }

        $queued = $this->delivery->queueFromTicketMessage($message);

        if ($queued) {
            Log::channel('stack')->info('email.outbound.scheduled_from_message', [
                'ticket_id' => $message->ticket_id,
                'message_id' => $message->id,
                'outbound_id' => $queued->id,
            ]);
        }
    }
}
