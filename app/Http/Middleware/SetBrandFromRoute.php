<?php

namespace App\Http\Middleware;

use App\Modules\Helpdesk\Models\Brand;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetBrandFromRoute
{
    public function __construct(private readonly TenantContext $context)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Brand|null $brand */
        $brand = $request->route('brand');

        if ($brand instanceof Brand) {
            $this->context->setTenantId($brand->tenant_id);
            $this->context->setBrandId($brand->id);
        }

        try {
            return $next($request);
        } finally {
            $this->context->clear();
        }
    }
}
