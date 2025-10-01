<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmailInboundMessageResource;
use App\Modules\Helpdesk\Models\EmailInboundMessage;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class EmailInboundMessageController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', EmailInboundMessage::class);

        $messages = EmailInboundMessage::query()
            ->latest('received_at')
            ->paginate(25);

        return EmailInboundMessageResource::collection($messages);
    }

    public function show(EmailInboundMessage $message): EmailInboundMessageResource
    {
        Gate::authorize('view', $message);

        return new EmailInboundMessageResource($message);
    }
}
