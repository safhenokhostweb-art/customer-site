@php
    $position = $position ?? 'header';
    $ads = \App\Helpers\SiteHelper::moduleEnabled('advertisements')
        ? \App\Models\Advertisement::active()->position($position)->orderBy('order')->get()
        : collect();
@endphp
@if($ads->isNotEmpty())
<div class="advertisements-zone advertisements-{{ $position }}" data-position="{{ $position }}">
    @foreach($ads as $ad)
        <div class="ad-item ad-type-{{ $ad->type }}" data-id="{{ $ad->id }}">
            @if($ad->type === 'banner')
                @if($ad->link)
                    <a href="{{ $ad->link }}" target="_blank" rel="noopener" class="block">
                        @if($ad->image_path)
                            <img src="{{ \App\Helpers\ImageHelper::url($ad->image_path) }}" alt="{{ $ad->title }}" class="w-full max-h-48 object-contain">
                        @else
                            <span class="block p-4 bg-slate-100 text-slate-700">{{ $ad->title ?: $ad->content }}</span>
                        @endif
                    </a>
                @else
                    @if($ad->image_path)
                        <img src="{{ \App\Helpers\ImageHelper::url($ad->image_path) }}" alt="{{ $ad->title }}" class="w-full max-h-48 object-contain">
                    @else
                        <span class="block p-4 bg-slate-100 text-slate-700">{{ $ad->title ?: $ad->content }}</span>
                    @endif
                @endif
            @elseif($ad->type === 'scrolling_text' || $ad->type === 'marquee')
                <div class="ad-marquee overflow-hidden py-2">
                    <marquee behavior="scroll" direction="rtl" scrollamount="3">{{ $ad->content ?: $ad->title }}</marquee>
                </div>
            @elseif($ad->type === 'typewriter')
                <div class="ad-typewriter py-2 min-h-[1.5em]" data-text="{{ e($ad->content ?: $ad->title) }}"><span class="typewriter-text"></span><span class="typewriter-cursor">|</span></div>
            @elseif($ad->type === 'blink')
                <div class="ad-blink py-2 text-center" style="animation: ad-blink 1s ease-in-out infinite;">{{ $ad->content ?: $ad->title }}</div>
            @elseif($ad->type === 'fade')
                <div class="ad-fade py-2 text-center" style="animation: ad-fade 4s ease-in-out infinite;">{{ $ad->content ?: $ad->title }}</div>
            @else
                <div class="py-2">{{ $ad->content ?: $ad->title }}</div>
            @endif
        </div>
    @endforeach
</div>
@endif
