@php
    $platformNotifs = collect();
    if (class_exists(\App\Services\PlatformUxService::class)) {
        $platformNotifs = collect(app(\App\Services\PlatformUxService::class)->getPlatformNotifications())
            ->filter(fn($n) => ($n['audience'] ?? 'public') === 'admin');
    }
    $allNotifs = $platformNotifs->map(fn($n) => [
        'id' => 'platform_' . md5(($n['title'] ?? '') . ($n['body'] ?? '')),
        'title' => $n['title'] ?? '',
        'body' => $n['body'] ?? null,
        'type' => $n['type'] ?? 'info',
        'link' => $n['link'] ?? null,
        'link_text' => $n['link_text'] ?? null,
        'image_url' => $n['image_url'] ?? null,
    ]);
@endphp
@if(true)
<style>
@keyframes admin-bell-shake {
    0%,100%{transform:rotate(0)}
    10%,30%{transform:rotate(-12deg)}
    20%,40%{transform:rotate(12deg)}
    50%{transform:rotate(0)}
}
.admin-notif-bell.has-unread { animation: admin-bell-shake 2s ease-in-out infinite; }
.admin-notif-bell.has-unread:hover { animation: none; }
</style>
@php
    $notifWidgetPos = class_exists(\App\Helpers\PlatformUx::class) ? \App\Helpers\PlatformUx::adminNotifWidgetPositionClasses() : 'bottom-6 right-6';
    $isTop = str_contains($notifWidgetPos, 'top-');
    $isLeft = str_contains($notifWidgetPos, 'left-');
@endphp
<div id="admin-notifications-widget" class="fixed {{ $notifWidgetPos }} z-[99998]" data-panel-vertical="{{ $isTop ? 'below' : 'above' }}" data-panel-horizontal="{{ $isLeft ? 'left' : 'right' }}">
    <button type="button" onclick="window.toggleAdminNotificationsPanel && window.toggleAdminNotificationsPanel(event)" class="flex items-center justify-center w-12 h-12 rounded-full bg-indigo-600 text-white shadow-lg hover:bg-indigo-700 transition-colors relative border-0 cursor-pointer admin-notif-bell {{ $allNotifs->isNotEmpty() ? 'has-unread' : '' }}" title="اعلان‌های ادمین از پلتفرم">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        @if($allNotifs->isNotEmpty())
        <span style="position:absolute;top:-4px;right:-4px;min-width:1.25rem;height:1.25rem;padding:0 4px;border-radius:9999px;background:#ef4444;color:#fff;font-size:0.7rem;font-weight:700;display:flex;align-items:center;justify-content:center;">{{ $allNotifs->count() }}</span>
        @endif
    </button>
    <div id="admin-notifications-panel" style="display:none;position:absolute;width:min(320px,90vw);max-height:60vh;overflow-y:auto;background:#fff;border-radius:0.5rem;box-shadow:0 10px 40px rgba(0,0,0,0.15);border:1px solid #e5e7eb;z-index:99999;">
        <div style="padding:0.75rem 1rem;border-bottom:1px solid #e5e7eb;font-weight:600;font-size:0.9rem;">اعلان‌های ادمین</div>
        @if($allNotifs->isEmpty())
        <div style="padding:1.5rem 1rem;color:#6b7280;font-size:0.9rem;text-align:center;">
            اعلانی وجود ندارد.<br>
            <a href="{{ route('admin.license.refresh-config.get') }}" class="text-indigo-600 hover:underline mt-2 inline-block font-medium">کلیک کنید: بازخوانی تنظیمات و اعلان‌ها</a><br>
            <span style="font-size:0.8rem;margin-top:0.5rem;display:block;">اگر باز هم نمایش داده نشد، لایسنس این سایت در پلتفرم باید به همان سایتی («سایت پیشخوان فروش محصولات») متصل باشد که اعلان برایش ساخته شده است.</span>
        </div>
        @else
        @foreach($allNotifs as $n)
        <div style="padding:0.75rem 1rem;border-bottom:1px solid #f3f4f6;">
            @if(!empty($n['image_url']))
            <div style="margin-bottom:0.5rem;"><img src="{{ $n['image_url'] }}" alt="" style="max-width:100%;max-height:80px;object-fit:contain;border-radius:4px;"></div>
            @endif
            <div style="font-weight:600;font-size:0.9rem;">{{ $n['title'] ?: 'اعلان' }}</div>
            @if($n['body'])<div style="font-size:0.8rem;color:#6b7280;margin-top:0.2rem;">{{ Str::limit($n['body'], 80) }}</div>@endif
            @if($n['link'])<a href="{{ $n['link'] }}" target="_blank" rel="noopener" class="text-indigo-600 text-sm mt-1 inline-block">{{ $n['link_text'] ?: 'مشاهده' }}</a>@endif
        </div>
        @endforeach
        @endif
    </div>
</div>
<style>
#admin-notifications-panel[data-vertical="above"] { bottom: calc(100% + 0.5rem); top: auto; }
#admin-notifications-panel[data-vertical="below"] { top: calc(100% + 0.5rem); bottom: auto; }
#admin-notifications-panel[data-horizontal="right"] { right: 0; left: auto; }
#admin-notifications-panel[data-horizontal="left"] { left: 0; right: auto; }
</style>
<script>
(function(){
    var p = document.getElementById('admin-notifications-panel');
    var w = document.getElementById('admin-notifications-widget');
    function applyPanelPosition() {
        if (!p || !w) return;
        var v = w.getAttribute('data-panel-vertical') || 'above';
        var h = w.getAttribute('data-panel-horizontal') || 'right';
        p.setAttribute('data-vertical', v);
        p.setAttribute('data-horizontal', h);
    }
    function toggle() {
        var e = arguments[0]; if (e) { e.preventDefault(); e.stopPropagation(); }
        if (!p || !w) return;
        applyPanelPosition();
        p.style.display = p.style.display === 'block' ? 'none' : 'block';
    }
    window.toggleAdminNotificationsPanel = toggle;
    if (p && w) {
        applyPanelPosition();
        p.addEventListener('click', function(e){ e.stopPropagation(); });
        document.addEventListener('click', function(e){
            if (p.style.display === 'block' && !w.contains(e.target)) p.style.display = 'none';
        });
    }
})();
</script>
@endif
