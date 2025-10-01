<?php

namespace App\Modules\Helpdesk\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketWorkflowTransition extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'from_status_id',
        'to_status_id',
        'requires_comment',
        'requires_resolution_note',
        'metadata',
    ];

    protected $casts = [
        'requires_comment' => 'boolean',
        'requires_resolution_note' => 'boolean',
        'metadata' => 'array',
    ];

    public function fromStatus(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'from_status_id');
    }

    public function toStatus(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'to_status_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
