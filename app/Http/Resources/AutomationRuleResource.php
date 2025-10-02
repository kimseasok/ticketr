<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\Helpdesk\Models\AutomationRule */
class AutomationRuleResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'event' => $this->event,
            'match_type' => $this->match_type,
            'conditions' => $this->conditions,
            'actions' => $this->actions,
            'is_active' => $this->is_active,
            'run_order' => $this->run_order,
            'last_run_at' => optional($this->last_run_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
