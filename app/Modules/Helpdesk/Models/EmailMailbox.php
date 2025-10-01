<?php

namespace App\Modules\Helpdesk\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailMailbox extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'brand_id',
        'name',
        'slug',
        'direction',
        'protocol',
        'host',
        'port',
        'encryption',
        'username',
        'credentials',
        'settings',
        'sync_state',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'sync_state' => 'array',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'credentials' => 'encrypted:array',
    ];

    protected $hidden = [
        'credentials',
    ];

    public function scopeActive(Builder $builder): Builder
    {
        return $builder->where($builder->qualifyColumn('is_active'), true);
    }

    public function scopeOutbound(Builder $builder): Builder
    {
        return $builder->whereIn($builder->qualifyColumn('direction'), ['outbound', 'bidirectional']);
    }

    public function scopeInbound(Builder $builder): Builder
    {
        return $builder->whereIn($builder->qualifyColumn('direction'), ['inbound', 'bidirectional']);
    }

    public function scopeForBrand(Builder $builder, ?int $brandId): Builder
    {
        if ($brandId === null) {
            return $builder;
        }

        return $builder->where(function (Builder $query) use ($brandId) {
            $query->whereNull($query->qualifyColumn('brand_id'))
                ->orWhere($query->qualifyColumn('brand_id'), $brandId);
        });
    }

    public function supportsInbound(): bool
    {
        return in_array($this->direction, ['inbound', 'bidirectional'], true);
    }

    public function supportsOutbound(): bool
    {
        return in_array($this->direction, ['outbound', 'bidirectional'], true);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function inboundMessages(): HasMany
    {
        return $this->hasMany(EmailInboundMessage::class, 'mailbox_id');
    }

    public function outboundMessages(): HasMany
    {
        return $this->hasMany(EmailOutboundMessage::class, 'mailbox_id');
    }
}
