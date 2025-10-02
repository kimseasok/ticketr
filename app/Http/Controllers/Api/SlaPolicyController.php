<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSlaPolicyRequest;
use App\Http\Requests\UpdateSlaPolicyRequest;
use App\Http\Resources\SlaPolicyResource;
use App\Modules\Helpdesk\Models\AuditLog;
use App\Modules\Helpdesk\Models\SlaPolicy;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class SlaPolicyController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', SlaPolicy::class);

        $query = SlaPolicy::query()->with('escalationUser');

        if ($brandId = $this->tenantContext->getBrandId()) {
            $query->where(function ($query) use ($brandId): void {
                $query->whereNull('brand_id')->orWhere('brand_id', $brandId);
            });
        }

        $policies = $query->orderBy('name')->paginate(perPage: 15);

        return SlaPolicyResource::collection($policies);
    }

    public function store(StoreSlaPolicyRequest $request): JsonResponse
    {
        Gate::authorize('create', SlaPolicy::class);

        $payload = $request->validated();
        $this->enforceScope($payload);

        $policy = SlaPolicy::create($payload);
        $this->recordAudit($policy, 'created');

        Log::channel('structured')->info('sla.policy.created', [
            'policy_id' => $policy->id,
            'tenant_id' => $policy->tenant_id,
            'brand_id' => $policy->brand_id,
        ]);

        return (new SlaPolicyResource($policy))->response()->setStatusCode(201);
    }

    public function show(SlaPolicy $slaPolicy): SlaPolicyResource
    {
        Gate::authorize('view', $slaPolicy);
        $this->assertAccessible($slaPolicy);

        return new SlaPolicyResource($slaPolicy);
    }

    public function update(UpdateSlaPolicyRequest $request, SlaPolicy $slaPolicy): SlaPolicyResource
    {
        Gate::authorize('update', $slaPolicy);
        $this->assertAccessible($slaPolicy);

        $payload = $request->validated();
        $this->enforceScope($payload, $slaPolicy);

        $original = $slaPolicy->getOriginal();
        $slaPolicy->update($payload);

        $this->recordAudit($slaPolicy, 'updated', $original);

        Log::channel('structured')->info('sla.policy.updated', [
            'policy_id' => $slaPolicy->id,
            'tenant_id' => $slaPolicy->tenant_id,
        ]);

        return new SlaPolicyResource($slaPolicy);
    }

    public function destroy(SlaPolicy $slaPolicy): JsonResponse
    {
        Gate::authorize('delete', $slaPolicy);
        $this->assertAccessible($slaPolicy);

        $this->recordAudit($slaPolicy, 'deleted', $slaPolicy->getAttributes());
        $slaPolicy->delete();

        Log::channel('structured')->info('sla.policy.deleted', [
            'policy_id' => $slaPolicy->id,
            'tenant_id' => $slaPolicy->tenant_id,
        ]);

        return response()->json(status: 204);
    }

    private function enforceScope(array &$payload, ?SlaPolicy $policy = null): void
    {
        $tenantId = $this->tenantContext->getTenantId();
        $brandId = $this->tenantContext->getBrandId();

        if ($tenantId !== null) {
            $payload['tenant_id'] = $tenantId;
        } elseif ($policy) {
            $payload['tenant_id'] = $policy->tenant_id;
        }

        if ($brandId !== null) {
            $targetBrand = Arr::get($payload, 'brand_id', $policy?->brand_id);
            if ($targetBrand !== null && (int) $targetBrand !== $brandId) {
                abort(403, __('SLA policies must belong to your active brand.'));
            }
            $payload['brand_id'] = $brandId;
        }
    }

    private function assertAccessible(SlaPolicy $policy): void
    {
        $tenantId = $this->tenantContext->getTenantId();
        $brandId = $this->tenantContext->getBrandId();

        if ($tenantId !== null && $policy->tenant_id !== $tenantId) {
            abort(404);
        }

        if ($brandId !== null && $policy->brand_id !== null && $policy->brand_id !== $brandId) {
            abort(404);
        }
    }

    private function recordAudit(SlaPolicy $policy, string $action, array $original = []): void
    {
        $newValues = $policy->auditPayload();
        $oldValues = $original ? Arr::only($original, array_keys($newValues)) : null;

        AuditLog::create([
            'tenant_id' => $policy->tenant_id,
            'brand_id' => $policy->brand_id,
            'user_id' => auth()->id(),
            'action' => "sla_policy.{$action}",
            'auditable_type' => SlaPolicy::class,
            'auditable_id' => $policy->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => [
                'priority_scope' => $policy->priority_scope,
            ],
        ]);
    }
}
