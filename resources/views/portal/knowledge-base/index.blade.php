<x-portal-layout :brand="$brand" title="Knowledge base">
    <div class="card">
        <h2 style="margin-top:0;">Knowledge base</h2>
        <p style="color:#475569;">Browse product updates, onboarding guides, and troubleshooting tips.</p>
        @if($articles->isEmpty())
            <div class="empty-state">No articles published yet. Check back soon.</div>
        @else
            <ul style="list-style:none;padding:0;margin:0;display:grid;gap:1.5rem;">
                @foreach($articles as $article)
                    <li style="padding:1.5rem;border-radius:1rem;border:1px solid #e2e8f0;background:#fff;">
                        <h3 style="margin:0;"><a href="{{ route('portal.knowledge.show', [$brand->slug, $article->slug]) }}" style="text-decoration:none;color:var(--brand-primary);">{{ $article->title }}</a></h3>
                        <p style="color:#475569;margin:0.5rem 0 0;">{{ \Illuminate\Support\Str::limit(strip_tags($article->content), 160) }}</p>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-portal-layout>
