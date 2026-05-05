@if(\App\Helpers\SiteHelper::moduleEnabled('social_links'))
    @php $links = \App\Models\SocialLink::where('is_active', true)->orderBy('order')->get(); @endphp
    @if($links->isNotEmpty())
        <div style="display:flex;flex-wrap:wrap;gap:0.75rem;margin-top:0.5rem;">
            @foreach($links as $l)
                <a href="{{ $l->url }}" target="_blank" rel="noopener" aria-label="{{ \App\Models\SocialLink::platforms()[$l->platform] ?? $l->platform }}" style="color:#6b7280;text-decoration:none;">{{ \App\Models\SocialLink::platforms()[$l->platform] ?? $l->platform }}</a>
            @endforeach
        </div>
    @endif
@endif
