<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Helpdesk\Models\Ticket */
class TicketResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'brand_id' => $this->brand_id,
            'contact_id' => $this->contact_id,
            'company_id' => $this->company_id,
            'created_by' => $this->created_by,
            'assigned_to' => $this->assigned_to,
            'subject' => $this->subject,
            'description' => $this->description,
            'status' => $this->status,
            'priority' => $this->priority,
            'channel' => $this->channel,
            'reference' => $this->reference,
            'metadata' => $this->metadata,
            'status_changed_at' => optional($this->status_changed_at)->toIso8601String(),
            'first_response_due_at' => optional($this->first_response_due_at)->toIso8601String(),
            'resolution_due_at' => optional($this->resolution_due_at)->toIso8601String(),
            'first_responded_at' => optional($this->first_responded_at)->toIso8601String(),
            'resolved_at' => optional($this->resolved_at)->toIso8601String(),
            'closed_at' => optional($this->closed_at)->toIso8601String(),
            'archived_at' => optional($this->archived_at)->toIso8601String(),
            'last_customer_reply_at' => optional($this->last_customer_reply_at)->toIso8601String(),
            'last_agent_reply_at' => optional($this->last_agent_reply_at)->toIso8601String(),
            'last_activity_at' => optional($this->last_activity_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
            'deleted_at' => optional($this->deleted_at)->toIso8601String(),
            'categories' => TicketCategoryResource::collection($this->whenLoaded('categories')),
            'tags' => TicketTagResource::collection($this->whenLoaded('tags')),
            'status_definition' => new TicketStatusResource($this->whenLoaded('statusDefinition')),
            'priority_definition' => new TicketPriorityResource($this->whenLoaded('priorityDefinition')),
            'sla' => $this->safeMetadata()['sla'] ?? null,
        ];
    }
}
use App\Http\Resources\TicketPriorityResource;
use App\Http\Resources\TicketStatusResource;
