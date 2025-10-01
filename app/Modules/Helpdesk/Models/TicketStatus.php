<?php

namespace App\Modules\Helpdesk\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketStatus extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'sort_order',
        'is_default',
        'first_response_minutes',
        'resolution_minutes',
        'metadata',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'metadata' => 'array',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'status', 'slug');
    }

    public function outgoingTransitions(): HasMany
    {
        return $this->hasMany(TicketWorkflowTransition::class, 'from_status_id');
    }

    public function incomingTransitions(): HasMany
    {
        return $this->hasMany(TicketWorkflowTransition::class, 'to_status_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
