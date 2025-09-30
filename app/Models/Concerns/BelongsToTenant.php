<?php

namespace App\Models\Concerns;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::creating(function (Model $model): void {
            if ($model->getAttribute('tenant_id')) {
                return;
            }

            $tenantId = app(TenantContext::class)->getTenantId();
            if ($tenantId !== null) {
                $model->setAttribute('tenant_id', $tenantId);
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenantId = app(TenantContext::class)->getTenantId();
            if ($tenantId === null) {
                return;
            }

            $builder->where($builder->qualifyColumn('tenant_id'), $tenantId);
        });
    }

    public function scopeForTenant(Builder $builder, int $tenantId): Builder
    {
        return $builder->where($builder->qualifyColumn('tenant_id'), $tenantId);
    }
}
