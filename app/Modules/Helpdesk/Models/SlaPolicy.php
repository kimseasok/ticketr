<?php

namespace App\Modules\Helpdesk\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

class SlaPolicy extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'brand_id',
        'name',
        'slug',
        'description',
        'priority_scope',
        'channel_scope',
        'first_response_minutes',
        'resolution_minutes',
        'grace_minutes',
        'is_active',
        'escalation_user_id',
        'alert_after_minutes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function escalationUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalation_user_id');
    }

    public function transitions(): HasMany
    {
        return $this->hasMany(SlaTransition::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function auditPayload(): array
    {
        return Arr::only($this->getAttributes(), [
            'id',
            'tenant_id',
            'brand_id',
            'name',
            'slug',
            'priority_scope',
            'channel_scope',
            'first_response_minutes',
            'resolution_minutes',
            'is_active',
        ]);
    }
}
