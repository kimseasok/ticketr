<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Helpdesk\Models\EmailOutboundMessage */
class EmailOutboundMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mailbox_id' => $this->mailbox_id,
            'ticket_id' => $this->ticket_id,
            'ticket_message_id' => $this->ticket_message_id,
            'subject' => $this->subject,
            'to_recipients' => $this->to_recipients,
            'cc_recipients' => $this->cc_recipients,
            'bcc_recipients' => $this->bcc_recipients,
            'status' => $this->status,
            'attempts' => $this->attempts,
            'provider_message_id' => $this->provider_message_id,
            'scheduled_at' => optional($this->scheduled_at)->toIso8601String(),
            'last_attempted_at' => optional($this->last_attempted_at)->toIso8601String(),
            'sent_at' => optional($this->sent_at)->toIso8601String(),
            'last_error' => $this->last_error,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
