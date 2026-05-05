@php
    $channels = \App\Models\MessagingChannel::where('is_active', true)->orderBy('order')->get();
    $activeChannels = $channels->filter(fn($c) => $c->link);
    $widgetLogo = \App\Models\Setting::get('messaging_widget_logo', '');
@endphp
@if(\App\Helpers\SiteHelper::moduleEnabled('whatsapp') && $activeChannels->isNotEmpty())
<div id="messaging-widget" style="position:fixed;bottom:1.5rem;left:1.5rem;z-index:9999;">
    <button type="button" id="messaging-widget-toggle" aria-label="ارتباط با ما" style="width:3.5rem;height:3.5rem;border-radius:50%;background:var(--platform-primary,#4f46e5);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,0.2);overflow:hidden;">
        @if($widgetLogo)
            <img src="{{ \App\Helpers\ImageHelper::url($widgetLogo) }}" alt="چت" style="width:100%;height:100%;object-fit:cover;">
        @else
            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        @endif
    </button>
    <div id="messaging-panel" style="display:none;position:absolute;bottom:4rem;left:0;min-width:220px;background:#fff;border-radius:0.75rem;box-shadow:0 10px 25px rgba(0,0,0,0.15);border:1px solid #e5e7eb;overflow:hidden;">
        <div style="padding:0.75rem 1rem;border-bottom:1px solid #e5e7eb;font-weight:600;font-size:0.9rem;">ارتباط با ما</div>
        <div style="padding:0.5rem;max-height:320px;overflow-y:auto;">
            @foreach($activeChannels as $ch)
                @php
                    $names = ['whatsapp'=>'واتساپ','telegram'=>'تلگرام','eitaa'=>'ایتا','bale'=>'بله','soroush'=>'سروش','gap'=>'گپ','igap'=>'آی‌گپ','rubika'=>'روبیکا','custom'=>$ch->custom_label ?: 'سفارشی'];
                    $label = $ch->custom_label ?: ($names[$ch->platform] ?? $ch->platform);
                @endphp
                <a href="{{ $ch->link }}" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:0.75rem;padding:0.6rem 0.75rem;text-decoration:none;color:#374151;border-radius:0.5rem;transition:background 0.15s;" class="messaging-channel-link" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='transparent'">
                    @if($ch->custom_image ?? null)
                        <img src="{{ \App\Helpers\ImageHelper::url($ch->custom_image) }}" alt="{{ $label }}" style="width:32px;height:32px;object-fit:contain;border-radius:6px;">
                    @else
                        <span style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            @include('partials.messaging-icons', ['platform' => $ch->platform])
                        </span>
                    @endif
                    <span style="font-size:0.9rem;">{{ $label }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>
<script>
(function(){
    var btn = document.getElementById('messaging-widget-toggle');
    var panel = document.getElementById('messaging-panel');
    if (btn && panel) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        });
        document.addEventListener('click', function() {
            panel.style.display = 'none';
        });
        panel.addEventListener('click', function(e) { e.stopPropagation(); });
    }
})();
</script>
@endif
