<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PerformTicketBulkActionRequest;
use App\Jobs\ProcessTicketBulkAction;
use App\Modules\Helpdesk\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class TicketBulkActionController extends Controller
{
    public function store(PerformTicketBulkActionRequest $request): JsonResponse
    {
        Gate::authorize('create', Ticket::class);

        $job = new ProcessTicketBulkAction($request->user()->id, $request->validated());
        $result = $job->handle(
            app(\App\Modules\Helpdesk\Services\TicketBulkActionService::class),
            app(\App\Support\Tenancy\TenantContext::class)
        );

        return response()->json(['data' => $result], Response::HTTP_ACCEPTED);
    }
}
