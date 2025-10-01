<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmailMailboxRequest;
use App\Http\Requests\UpdateEmailMailboxRequest;
use App\Http\Resources\EmailMailboxResource;
use App\Modules\Helpdesk\Models\EmailMailbox;
use App\Modules\Helpdesk\Services\Email\EmailIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class EmailMailboxController extends Controller
{
    public function __construct(private readonly EmailIngestionService $ingestion)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', EmailMailbox::class);

        $mailboxes = EmailMailbox::query()
            ->latest('updated_at')
            ->paginate(25);

        return EmailMailboxResource::collection($mailboxes);
    }

    public function store(StoreEmailMailboxRequest $request): JsonResponse
    {
        Gate::authorize('create', EmailMailbox::class);

        $mailbox = EmailMailbox::create($this->payloadFromRequest($request));

        return (new EmailMailboxResource($mailbox))->response()->setStatusCode(201);
    }

    public function show(EmailMailbox $mailbox): EmailMailboxResource
    {
        Gate::authorize('view', $mailbox);

        return new EmailMailboxResource($mailbox);
    }

    public function update(UpdateEmailMailboxRequest $request, EmailMailbox $mailbox): EmailMailboxResource
    {
        Gate::authorize('update', $mailbox);

        $mailbox->fill($this->payloadFromRequest($request, $mailbox))->save();

        return new EmailMailboxResource($mailbox->fresh());
    }

    public function destroy(EmailMailbox $mailbox): JsonResponse
    {
        Gate::authorize('delete', $mailbox);

        $mailbox->delete();

        return response()->json(['status' => 'deleted']);
    }

    public function sync(EmailMailbox $mailbox): JsonResponse
    {
        Gate::authorize('sync', $mailbox);

        $fetched = $this->ingestion->synchronizeMailbox($mailbox);
        $processed = $this->ingestion->processMailbox($mailbox);

        return response()->json([
            'fetched' => count($fetched),
            'processed' => count($processed),
        ]);
    }

    private function payloadFromRequest(Request $request, ?EmailMailbox $mailbox = null): array
    {
        $data = $request->validated();

        if (! array_key_exists('credentials', $data) && $mailbox) {
            $data['credentials'] = $mailbox->credentials;
        }

        return $data;
    }
}
