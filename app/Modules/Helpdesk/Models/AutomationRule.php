<?php

namespace App\Modules\Helpdesk\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

class AutomationRule extends Model
{
    use BelongsToTenant;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'brand_id',
        'name',
        'slug',
        'event',
        'match_type',
        'conditions',
        'actions',
        'is_active',
        'run_order',
        'last_run_at',
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AutomationRuleVersion::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(AutomationRuleExecution::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('run_order')->orderBy('id');
    }

    public function auditPayload(): array
    {
        return Arr::only($this->getAttributes(), [
            'id',
            'tenant_id',
            'brand_id',
            'name',
            'slug',
            'event',
            'match_type',
            'is_active',
            'run_order',
        ]);
    }
}
