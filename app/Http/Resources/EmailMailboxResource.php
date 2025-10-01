<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Helpdesk\Models\EmailMailbox */
class EmailMailboxResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'direction' => $this->direction,
            'protocol' => $this->protocol,
            'host' => $this->host,
            'port' => $this->port,
            'encryption' => $this->encryption,
            'username_hash' => substr(hash('sha256', (string) $this->username), 0, 16),
            'is_active' => (bool) $this->is_active,
            'brand_id' => $this->brand_id,
            'last_synced_at' => optional($this->last_synced_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
