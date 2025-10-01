<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Modules\Helpdesk\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringToken extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'token_hash',
        'scopes',
        'last_used_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'last_used_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function markUsed(): void
    {
        $this->forceFill(['last_used_at' => now()])->save();
    }
}
