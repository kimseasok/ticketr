<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IngestTicketMessageRequest;
use App\Http\Resources\TicketMessageResource;
use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Models\TicketMessage;
use App\Modules\Helpdesk\Services\TicketMessageService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ChannelIngestionController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TicketMessageService $service
    ) {
    }

    public function store(IngestTicketMessageRequest $request, Ticket $ticket): JsonResponse
    {
        $this->assertTicketAccessible($ticket);
        $this->assertChannelToken($request->header('X-Channel-Token'));

        Gate::authorize('create', [TicketMessage::class, $ticket]);

        $message = $this->service->append($ticket, $request->validated());

        Log::channel('stack')->info('channel.message_ingested', [
            'tenant_id' => $ticket->tenant_id,
            'brand_id' => $ticket->brand_id,
            'ticket_id' => $ticket->id,
            'channel' => $message->channel,
            'visibility' => $message->visibility,
        ]);

        return (new TicketMessageResource($message->load('attachments')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    private function assertTicketAccessible(Ticket $ticket): void
    {
        $tenantId = $this->tenantContext->getTenantId();
        $brandId = $this->tenantContext->getBrandId();

        if ($tenantId !== null && $ticket->tenant_id !== $tenantId) {
            abort(Response::HTTP_NOT_FOUND);
        }

        if ($brandId !== null && $ticket->brand_id !== null && $ticket->brand_id !== $brandId) {
            abort(Response::HTTP_NOT_FOUND);
        }
    }

    private function assertChannelToken(?string $token): void
    {
        $expected = config('services.channels.ingestion_secret');

        if ($expected === null || $expected === '') {
            return;
        }

        if (! is_string($token) || ! hash_equals($expected, $token)) {
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid channel token.');
        }
    }
}
