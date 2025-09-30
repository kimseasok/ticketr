<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Modules\Helpdesk\Models\Ticket;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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

        $query = Ticket::query()->with(['contact', 'assignee', 'categories', 'tags']);

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
        $payload['reference'] ??= $this->generateReference();

        $ticket = Ticket::create($payload);

        $this->syncTaxonomy($ticket, $payload);

        return (new TicketResource($ticket->fresh(['contact', 'assignee', 'categories', 'tags'])))->response()->setStatusCode(201);
    }

    public function show(Ticket $ticket): TicketResource
    {
        Gate::authorize('view', $ticket);

        return new TicketResource($ticket->load(['contact', 'assignee', 'categories', 'tags']));
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): TicketResource
    {
        Gate::authorize('update', $ticket);

        $payload = $request->validated();

        if (isset($payload['status']) && $payload['status'] === Ticket::STATUS_ARCHIVED) {
            $payload['archived_at'] = now();
        }

        $ticket->update($payload);

        $this->syncTaxonomy($ticket, $payload);

        return new TicketResource($ticket->fresh(['contact', 'assignee', 'categories', 'tags']));
    }

    public function destroy(Ticket $ticket): JsonResponse
    {
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
}
