<x-portal-layout :brand="$brand" title="Ticket submitted">
    <div class="card" style="text-align:center;">
        <h2>Thanks for reaching out!</h2>
        <p style="color:#475569;">Your ticket reference is <span class="badge">{{ $ticket->reference }}</span>.</p>
        <p>We'll send an email update to you as soon as an agent responds.</p>
        <a class="button" href="{{ route('portal.tickets.show', [$brand->slug, $ticket->reference]) }}">View ticket status</a>
    </div>
</x-portal-layout>
