<?php

namespace App\Modules\Helpdesk\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationRuleVersion extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'automation_rule_id',
        'tenant_id',
        'version',
        'definition',
        'created_by',
        'published_at',
    ];

    protected $casts = [
        'definition' => 'array',
        'published_at' => 'datetime',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class, 'automation_rule_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
