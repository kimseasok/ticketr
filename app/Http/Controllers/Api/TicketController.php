<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Modules\Helpdesk\Models\SlaPolicy;
use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Services\SlaPolicyService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Ticket::class);

        $query = Ticket::query()->with([
            'contact',
            'assignee',
            'categories',
            'tags',
            'statusDefinition',
            'priorityDefinition',
            'watcherParticipants',
        ]);

        if ($brandId = $this->tenantContext->getBrandId()) {
            $query->forBrand($brandId);
        }

        $tickets = $query
            ->latest('last_activity_at')
            ->paginate(perPage: 15);

        return TicketResource::collection($tickets);
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        Gate::authorize('create', Ticket::class);

        $payload = $request->validated();

        if (array_key_exists('assigned_to', $payload)) {
            Gate::authorize('assign', Ticket::class);
        }

        $watchers = Arr::pull($payload, 'watcher_ids', []);

        $payload['reference'] ??= $this->generateReference();

        $this->enforceScope($payload);

        $ticket = Ticket::create($payload);
        $this->applySlaPolicy($ticket, $payload['sla_policy_id'] ?? null);

        $this->syncTaxonomy($ticket, $payload);

        if ($watchers !== []) {
            Gate::authorize('manageWatchers', $ticket);
            $ticket->syncWatchers($watchers, auth()->id());
        }

        return (new TicketResource($ticket->fresh(['contact', 'assignee', 'categories', 'tags', 'statusDefinition', 'priorityDefinition', 'watcherParticipants'])))->response()->setStatusCode(201);
    }

    public function show(Ticket $ticket): TicketResource
    {
        $this->assertTicketAccessible($ticket);
        Gate::authorize('view', $ticket);

        return new TicketResource($ticket->load(['contact', 'assignee', 'categories', 'tags', 'statusDefinition', 'priorityDefinition', 'watcherParticipants']));
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): TicketResource
    {
        $this->assertTicketAccessible($ticket);
        Gate::authorize('update', $ticket);

        $payload = $request->validated();

        if (array_key_exists('assigned_to', $payload)) {
            Gate::authorize('assign', $ticket);
        }

        $watchers = Arr::pull($payload, 'watcher_ids', null);

        $this->enforceScope($payload, $ticket);

        if (isset($payload['status']) && $payload['status'] === Ticket::STATUS_ARCHIVED) {
            $payload['archived_at'] = now();
        }

        $ticket->update($payload);
        $this->applySlaPolicy($ticket, $payload['sla_policy_id'] ?? null);

        $this->syncTaxonomy($ticket, $payload);

        if ($watchers !== null) {
            Gate::authorize('manageWatchers', $ticket);
            $ticket->syncWatchers($watchers, auth()->id());
        }

        return new TicketResource($ticket->fresh(['contact', 'assignee', 'categories', 'tags', 'statusDefinition', 'priorityDefinition', 'watcherParticipants']));
    }

    public function destroy(Ticket $ticket): JsonResponse
    {
        $this->assertTicketAccessible($ticket);
        Gate::authorize('delete', $ticket);

        $ticket->delete();

        return (new TicketResource($ticket))->response()->setStatusCode(202);
    }

    private function generateReference(): string
    {
        return sprintf('T-%s', Str::upper(Str::random(8)));
    }

    private function syncTaxonomy(Ticket $ticket, array $payload): void
    {
        if (array_key_exists('category_ids', $payload)) {
            $ticket->syncCategories($payload['category_ids'], auth()->id());
        }

        if (array_key_exists('tag_ids', $payload)) {
            $ticket->syncTags($payload['tag_ids'], auth()->id());
        }
    }

    private function enforceScope(array &$payload, ?Ticket $ticket = null): void
    {
        $tenantId = $this->tenantContext->getTenantId();
        $brandId = $this->tenantContext->getBrandId();

        if ($tenantId !== null) {
            if ($ticket && $ticket->tenant_id !== $tenantId) {
                abort(403, __('You may only interact with tickets in your tenant.'));
            }

            if (isset($payload['tenant_id']) && (int) $payload['tenant_id'] !== $tenantId) {
                abort(403, __('You may only interact with tickets in your tenant.'));
            }

            $payload['tenant_id'] = $tenantId;
        }

        if ($brandId !== null) {
            if ($ticket && $ticket->brand_id !== null && $ticket->brand_id !== $brandId) {
                abort(403, __('You may only interact with tickets in your brand.'));
            }

            if (isset($payload['brand_id']) && (int) $payload['brand_id'] !== $brandId) {
                abort(403, __('You may only interact with tickets in your brand.'));
            }

            $payload['brand_id'] = $brandId;
        }
    }

    private function assertTicketAccessible(Ticket $ticket): void
    {
        $tenantId = $this->tenantContext->getTenantId();
        $brandId = $this->tenantContext->getBrandId();

        if ($tenantId !== null && $ticket->tenant_id !== $tenantId) {
            abort(404);
        }

        if ($brandId !== null && $ticket->brand_id !== null && $ticket->brand_id !== $brandId) {
            abort(404);
        }
    }

    private function applySlaPolicy(Ticket $ticket, $policyId): void
    {
        /** @var SlaPolicyService $service */
        $service = app(SlaPolicyService::class);

        $policy = null;
        if ($policyId) {
            $policy = SlaPolicy::query()
                ->where('tenant_id', $ticket->tenant_id)
                ->find((int) $policyId);
        }

        if ($policyId !== null) {
            $service->assignPolicy($ticket, $policy);
        } else {
            $service->refreshTicket($ticket);
        }

        $ticket->save();
    }
}
