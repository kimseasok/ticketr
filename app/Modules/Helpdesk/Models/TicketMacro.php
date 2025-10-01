<?php

namespace App\Modules\Helpdesk\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

class TicketMacro extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'brand_id',
        'name',
        'slug',
        'description',
        'body',
        'visibility',
        'metadata',
        'is_shared',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_shared' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function auditPayload(): array
    {
        return Arr::only($this->getAttributes(), [
            'id',
            'tenant_id',
            'brand_id',
            'name',
            'slug',
            'visibility',
            'is_shared',
        ]);
    }
}
