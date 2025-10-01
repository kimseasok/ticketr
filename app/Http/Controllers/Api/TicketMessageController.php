<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketMessageRequest;
use App\Http\Resources\TicketMessageResource;
use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Models\TicketMessage;
use App\Modules\Helpdesk\Services\TicketMessageService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TicketMessageController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TicketMessageService $service
    ) {
    }

    public function index(Request $request, Ticket $ticket): AnonymousResourceCollection
    {
        $this->assertTicketAccessible($ticket);
        Gate::authorize('viewAny', [TicketMessage::class, $ticket]);

        $messages = $ticket->messages()
            ->visibleTo($request->user())
            ->with('attachments')
            ->latest('posted_at')
            ->paginate(25);

        return TicketMessageResource::collection($messages);
    }

    public function store(StoreTicketMessageRequest $request, Ticket $ticket): JsonResponse
    {
        $this->assertTicketAccessible($ticket);
        Gate::authorize('create', [TicketMessage::class, $ticket]);

        $message = $this->service->append($ticket, $request->validated());

        return (new TicketMessageResource($message->load('attachments')))->response()->setStatusCode(201);
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
}
