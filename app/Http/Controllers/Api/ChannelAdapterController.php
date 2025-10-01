<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChannelAdapterRequest;
use App\Http\Requests\UpdateChannelAdapterRequest;
use App\Http\Resources\ChannelAdapterResource;
use App\Modules\Helpdesk\Models\ChannelAdapter;
use App\Modules\Helpdesk\Models\AuditLog;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class ChannelAdapterController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', ChannelAdapter::class);

        $query = ChannelAdapter::query()->with('brand');

        if ($brandId = $this->tenantContext->getBrandId()) {
            $query->where(function ($query) use ($brandId): void {
                $query->whereNull('brand_id')->orWhere('brand_id', $brandId);
            });
        }

        $adapters = $query->paginate(perPage: 15);

        return ChannelAdapterResource::collection($adapters);
    }

    public function store(StoreChannelAdapterRequest $request): JsonResponse
    {
        Gate::authorize('create', ChannelAdapter::class);

        $payload = $request->validated();
        $this->enforceScope($payload);

        $adapter = ChannelAdapter::create($payload);

        $this->recordAudit($adapter, 'created');

        Log::channel('stack')->info('channel_adapter.created', [
            'tenant_id' => $adapter->tenant_id,
            'brand_id' => $adapter->brand_id,
            'adapter_id' => $adapter->id,
            'channel' => $adapter->channel,
        ]);

        return (new ChannelAdapterResource($adapter))->response()->setStatusCode(201);
    }

    public function show(ChannelAdapter $channelAdapter): ChannelAdapterResource
    {
        Gate::authorize('view', $channelAdapter);

        return new ChannelAdapterResource($channelAdapter);
    }

    public function update(UpdateChannelAdapterRequest $request, ChannelAdapter $channelAdapter): ChannelAdapterResource
    {
        Gate::authorize('update', $channelAdapter);

        $payload = $request->validated();
        $this->enforceScope($payload, $channelAdapter);

        $original = $channelAdapter->getOriginal();

        $channelAdapter->update($payload);

        $this->recordAudit($channelAdapter, 'updated', $original);

        Log::channel('stack')->info('channel_adapter.updated', [
            'tenant_id' => $channelAdapter->tenant_id,
            'brand_id' => $channelAdapter->brand_id,
            'adapter_id' => $channelAdapter->id,
        ]);

        return new ChannelAdapterResource($channelAdapter);
    }

    public function destroy(ChannelAdapter $channelAdapter): JsonResponse
    {
        Gate::authorize('delete', $channelAdapter);

        $this->recordAudit($channelAdapter, 'deleted', $channelAdapter->getAttributes());

        $channelAdapter->delete();

        Log::channel('stack')->info('channel_adapter.deleted', [
            'tenant_id' => $channelAdapter->tenant_id,
            'brand_id' => $channelAdapter->brand_id,
            'adapter_id' => $channelAdapter->id,
        ]);

        return response()->json(status: 204);
    }

    private function enforceScope(array &$payload, ?ChannelAdapter $adapter = null): void
    {
        $tenantId = $this->tenantContext->getTenantId();
        $brandId = $this->tenantContext->getBrandId();

        if ($tenantId !== null) {
            $payload['tenant_id'] = $tenantId;
        } elseif ($adapter) {
            $payload['tenant_id'] = $adapter->tenant_id;
        }

        if ($brandId !== null) {
            $targetBrand = Arr::get($payload, 'brand_id', $adapter?->brand_id);
            if ($targetBrand !== null && (int) $targetBrand !== $brandId) {
                abort(403, __('Adapters must belong to your active brand.'));
            }
            $payload['brand_id'] = $brandId;
        }
    }

    private function recordAudit(ChannelAdapter $adapter, string $action, array $original = []): void
    {
        $newValues = $adapter->auditPayload();
        $oldValues = $original ? Arr::only($original, array_keys($newValues)) : null;

        AuditLog::create([
            'tenant_id' => $adapter->tenant_id,
            'brand_id' => $adapter->brand_id,
            'user_id' => auth()->id(),
            'action' => "channel_adapter.{$action}",
            'auditable_type' => ChannelAdapter::class,
            'auditable_id' => $adapter->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => [
                'channel' => $adapter->channel,
            ],
        ]);
    }
}
