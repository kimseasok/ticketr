<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Helpdesk\Models\EmailInboundMessage */
class EmailInboundMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mailbox_id' => $this->mailbox_id,
            'ticket_id' => $this->ticket_id,
            'ticket_message_id' => $this->ticket_message_id,
            'subject' => $this->subject,
            'from_email' => $this->from_email,
            'from_name' => $this->from_name,
            'to_recipients' => $this->to_recipients,
            'cc_recipients' => $this->cc_recipients,
            'status' => $this->status,
            'received_at' => optional($this->received_at)->toIso8601String(),
            'processed_at' => optional($this->processed_at)->toIso8601String(),
            'attachments_count' => $this->attachments_count,
            'headers' => $this->headers,
            'error_info' => $this->error_info,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
