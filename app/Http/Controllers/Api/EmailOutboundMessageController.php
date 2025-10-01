<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmailOutboundMessageResource;
use App\Modules\Helpdesk\Models\EmailOutboundMessage;
use App\Modules\Helpdesk\Services\Email\EmailDeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class EmailOutboundMessageController extends Controller
{
    public function __construct(private readonly EmailDeliveryService $delivery)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', EmailOutboundMessage::class);

        $messages = EmailOutboundMessage::query()
            ->latest('created_at')
            ->paginate(25);

        return EmailOutboundMessageResource::collection($messages);
    }

    public function show(EmailOutboundMessage $message): EmailOutboundMessageResource
    {
        Gate::authorize('view', $message);

        return new EmailOutboundMessageResource($message);
    }

    public function deliver(EmailOutboundMessage $message): JsonResponse
    {
        Gate::authorize('deliver', $message);

        $result = $this->delivery->deliver($message);

        return response()->json([
            'status' => $result->success ? 'sent' : 'failed',
            'provider_message_id' => $result->providerMessageId,
            'error' => $result->error,
        ], $result->success ? 200 : 422);
    }
}
