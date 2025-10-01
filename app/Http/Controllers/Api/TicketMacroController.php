<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketMacroRequest;
use App\Http\Requests\UpdateTicketMacroRequest;
use App\Http\Resources\TicketMacroResource;
use App\Modules\Helpdesk\Models\AuditLog;
use App\Modules\Helpdesk\Models\TicketMacro;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class TicketMacroController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', TicketMacro::class);

        $query = TicketMacro::query();

        if ($brandId = $this->tenantContext->getBrandId()) {
            $query->where(function ($query) use ($brandId): void {
                $query->whereNull('brand_id')->orWhere('brand_id', $brandId);
            });
        }

        return TicketMacroResource::collection($query->paginate(perPage: 15));
    }

    public function store(StoreTicketMacroRequest $request): JsonResponse
    {
        Gate::authorize('create', TicketMacro::class);

        $payload = $request->validated();
        $this->enforceScope($payload);

        if (($payload['visibility'] ?? 'tenant') === 'private') {
            $payload['metadata'] = array_merge($payload['metadata'] ?? [], [
                'owner_id' => auth()->id(),
            ]);
        }

        $macro = TicketMacro::create($payload);

        $this->recordAudit($macro, 'created');

        Log::channel('stack')->info('ticket_macro.created', [
            'tenant_id' => $macro->tenant_id,
            'brand_id' => $macro->brand_id,
            'macro_id' => $macro->id,
            'visibility' => $macro->visibility,
        ]);

        return (new TicketMacroResource($macro))->response()->setStatusCode(201);
    }

    public function show(TicketMacro $ticketMacro): TicketMacroResource
    {
        Gate::authorize('view', $ticketMacro);

        return new TicketMacroResource($ticketMacro);
    }

    public function update(UpdateTicketMacroRequest $request, TicketMacro $ticketMacro): TicketMacroResource
    {
        Gate::authorize('update', $ticketMacro);

        $payload = $request->validated();
        $this->enforceScope($payload, $ticketMacro);

        if (($payload['visibility'] ?? $ticketMacro->visibility) === 'private') {
            $payload['metadata'] = array_merge($ticketMacro->metadata ?? [], $payload['metadata'] ?? []);
            $payload['metadata']['owner_id'] = $ticketMacro->metadata['owner_id'] ?? auth()->id();
        }

        $original = $ticketMacro->getOriginal();

        $ticketMacro->update($payload);

        $this->recordAudit($ticketMacro, 'updated', $original);

        Log::channel('stack')->info('ticket_macro.updated', [
            'tenant_id' => $ticketMacro->tenant_id,
            'brand_id' => $ticketMacro->brand_id,
            'macro_id' => $ticketMacro->id,
        ]);

        return new TicketMacroResource($ticketMacro);
    }

    public function destroy(TicketMacro $ticketMacro): JsonResponse
    {
        Gate::authorize('delete', $ticketMacro);

        $this->recordAudit($ticketMacro, 'deleted', $ticketMacro->getAttributes());

        $ticketMacro->delete();

        Log::channel('stack')->info('ticket_macro.deleted', [
            'tenant_id' => $ticketMacro->tenant_id,
            'brand_id' => $ticketMacro->brand_id,
            'macro_id' => $ticketMacro->id,
        ]);

        return response()->json(status: 204);
    }

    private function enforceScope(array &$payload, ?TicketMacro $macro = null): void
    {
        $tenantId = $this->tenantContext->getTenantId();
        $brandId = $this->tenantContext->getBrandId();

        if ($tenantId !== null) {
            $payload['tenant_id'] = $tenantId;
        } elseif ($macro) {
            $payload['tenant_id'] = $macro->tenant_id;
        }

        if ($brandId !== null) {
            $targetBrand = Arr::get($payload, 'brand_id', $macro?->brand_id);
            if ($targetBrand !== null && (int) $targetBrand !== $brandId) {
                abort(403, __('Macros must belong to your active brand.'));
            }
            $payload['brand_id'] = $brandId;
        }
    }

    private function recordAudit(TicketMacro $macro, string $action, array $original = []): void
    {
        $newValues = $macro->auditPayload();
        $oldValues = $original ? Arr::only($original, array_keys($newValues)) : null;

        AuditLog::create([
            'tenant_id' => $macro->tenant_id,
            'brand_id' => $macro->brand_id,
            'user_id' => auth()->id(),
            'action' => "ticket_macro.{$action}",
            'auditable_type' => TicketMacro::class,
            'auditable_id' => $macro->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => [
                'visibility' => $macro->visibility,
            ],
        ]);
    }
}
