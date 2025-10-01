<x-portal-layout :brand="$brand" :title="'Ticket '.$ticket->reference">
    <div class="card">
        <h2 style="margin-top:0;">Ticket {{ $ticket->reference }}</h2>
        <p style="color:#475569;">Status: <span class="badge">{{ ucfirst($ticket->status) }}</span></p>
        @if($ticket->messages->isEmpty())
            <div class="empty-state">No updates have been posted yet. Our team will respond soon.</div>
        @else
            <ul class="timeline">
                @foreach($ticket->messages as $message)
                    <li>
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <strong>{{ $message->author_type === 'contact' ? 'You' : 'Support' }}</strong>
                            <span style="color:#64748b;font-size:0.875rem;">{{ $message->posted_at?->format('M j, Y g:i A') }}</span>
                        </div>
                        <p style="white-space:pre-wrap;color:#1e293b;">{{ $message->body }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-portal-layout>
