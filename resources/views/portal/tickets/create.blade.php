<x-portal-layout :brand="$brand" title="Submit a ticket">
    <div class="card">
        <h2 style="margin-top:0;">Submit a support ticket</h2>
        <p style="color:#475569;">We'll email you updates as soon as an agent responds.</p>
        @if ($errors->any())
            <div style="background:#fee2e2;color:#b91c1c;padding:1rem;border-radius:0.75rem;margin-bottom:1.5rem;">
                <strong>We found {{ $errors->count() }} issue(s):</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('portal.tickets.store', $brand->slug) }}">
            @csrf
            <label for="name">Full name</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required>

            <label for="email">Email address</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required>

            <label for="subject">Subject</label>
            <input type="text" name="subject" id="subject" value="{{ old('subject') }}" required>

            <label for="message">How can we help?</label>
            <textarea name="message" id="message" rows="6" required>{{ old('message') }}</textarea>

            <button type="submit" class="button">Submit ticket</button>
        </form>
    </div>
</x-portal-layout>
