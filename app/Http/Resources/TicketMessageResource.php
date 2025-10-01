<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Helpdesk\Models\TicketMessage */
class TicketMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'visibility' => $this->visibility,
            'channel' => $this->channel,
            'body' => $this->body,
            'metadata' => $this->metadata,
            'attachments_count' => $this->attachments_count,
            'posted_at' => optional($this->posted_at)->toIso8601String(),
            'author' => [
                'type' => $this->author_type,
                'id' => $this->author_id,
            ],
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
