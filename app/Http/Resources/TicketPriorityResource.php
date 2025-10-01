<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Helpdesk\Models\TicketPriority */
class TicketPriorityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'color' => $this->color,
            'is_default' => $this->is_default,
            'first_response_minutes' => $this->first_response_minutes,
            'resolution_minutes' => $this->resolution_minutes,
        ];
    }
}
