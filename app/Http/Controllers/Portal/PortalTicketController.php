<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\SubmitTicketRequest;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Contact;
use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Services\TicketMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PortalTicketController extends Controller
{
    public function __construct(private readonly TicketMessageService $messages)
    {
    }

    public function create(Brand $brand): View
    {
        return view('portal.tickets.create', [
            'brand' => $brand,
        ]);
    }

    public function store(SubmitTicketRequest $request, Brand $brand): RedirectResponse
    {
        $data = $request->validated();

        $contact = Contact::query()->firstOrCreate([
            'tenant_id' => $brand->tenant_id,
            'brand_id' => $brand->id,
            'email' => strtolower($data['email']),
        ], [
            'name' => $data['name'],
            'metadata' => ['source' => 'portal'],
        ]);

        $ticket = Ticket::query()->create([
            'tenant_id' => $brand->tenant_id,
            'brand_id' => $brand->id,
            'contact_id' => $contact->id,
            'subject' => $data['subject'],
            'description' => Str::limit($data['message'], 500),
            'status' => Ticket::STATUS_OPEN,
            'priority' => 'normal',
            'channel' => 'web',
            'reference' => sprintf('PT-%s', Str::upper(Str::random(8))),
            'metadata' => [
                'portal' => true,
                'ip' => $request->ip(),
                'locale' => app()->getLocale(),
            ],
        ]);

        $this->messages->append($ticket, [
            'author_type' => 'contact',
            'author_id' => $contact->id,
            'channel' => 'web',
            'body' => $data['message'],
            'metadata' => [
                'portal_submission' => true,
            ],
            'participants' => [
                [
                    'participant_type' => 'contact',
                    'participant_id' => $contact->id,
                    'role' => 'requester',
                ],
            ],
        ]);

        Log::channel('structured')->info('portal.ticket.submitted', [
            'tenant_id' => $brand->tenant_id,
            'brand_id' => $brand->id,
            'ticket_id' => $ticket->id,
            'reference' => $ticket->reference,
        ]);

        return redirect()
            ->route('portal.tickets.thanks', [$brand->slug, $ticket->reference])
            ->with('portal_ticket_reference', $ticket->reference);
    }

    public function thanks(Brand $brand, string $reference): View
    {
        $ticket = Ticket::query()
            ->forBrand($brand->id)
            ->where('reference', $reference)
            ->firstOrFail();

        return view('portal.tickets.thanks', [
            'brand' => $brand,
            'ticket' => $ticket,
        ]);
    }

    public function show(Brand $brand, string $reference): View
    {
        $ticket = Ticket::query()
            ->forBrand($brand->id)
            ->where('reference', $reference)
            ->firstOrFail();

        $ticket->load(['messages' => fn ($query) => $query->publicOnly()->orderBy('posted_at')]);

        return view('portal.tickets.show', [
            'brand' => $brand,
            'ticket' => $ticket,
        ]);
    }
}
