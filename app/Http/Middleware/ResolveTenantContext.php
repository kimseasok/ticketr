<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;

class ResolveTenantContext
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $tenantHeader = config('tenancy.tenant_header');
        $brandHeader = config('tenancy.brand_header');

        if ($tenantHeader && $request->hasHeader($tenantHeader)) {
            $this->context->setTenantId((int) $request->header($tenantHeader));
        } elseif ($user = $request->user()) {
            $this->context->setTenantId($user->tenant_id);
        }

        if ($brandHeader && $request->hasHeader($brandHeader)) {
            $this->context->setBrandId((int) $request->header($brandHeader));
        } elseif ($user = $request->user()) {
            $this->context->setBrandId($user->brand_id);
        }

        try {
            return $next($request);
        } finally {
            $this->context->clear();
        }
    }
}
