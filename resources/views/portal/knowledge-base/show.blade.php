<x-portal-layout :brand="$brand" :title="$article->title">
    <div class="card">
        <a href="{{ route('portal.knowledge.index', $brand->slug) }}" style="color:var(--brand-primary);text-decoration:none;font-weight:600;">&larr; Back to articles</a>
        <h2 style="margin-top:1rem;">{{ $article->title }}</h2>
        <div style="color:#475569;">
            {!! $article->content !!}
        </div>
    </div>
</x-portal-layout>
