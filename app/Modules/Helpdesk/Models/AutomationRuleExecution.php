<?php

namespace App\Modules\Helpdesk\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationRuleExecution extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'automation_rule_id',
        'tenant_id',
        'ticket_id',
        'trigger_event',
        'status',
        'result',
        'context',
        'executed_at',
    ];

    protected $casts = [
        'context' => 'array',
        'executed_at' => 'datetime',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
