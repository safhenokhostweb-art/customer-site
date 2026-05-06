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
.admin-notifications-widget {
    position: fixed;
    z-index: 99998;
}
.admin-notif-bell {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border-radius: 9999px;
    background: #4f46e5;
    color: #fff;
    border: 0;
    cursor: pointer;
    box-shadow: 0 10px 24px rgba(79, 70, 229, 0.24);
    transition: background .15s ease, transform .12s ease;
    position: relative;
    touch-action: none;
}
.admin-notif-bell:hover { background: #4338ca; }
.admin-notif-bell:active { transform: scale(.98); }
.admin-notif-bell svg {
    width: 18px;
    height: 18px;
}
.admin-notif-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    min-width: 1.1rem;
    height: 1.1rem;
    padding: 0 4px;
    border-radius: 9999px;
    background: #ef4444;
    color: #fff;
    font-size: .68rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
}
.admin-notif-drag-hint {
    position: absolute;
    inset-inline-end: 50%;
    transform: translateX(50%);
    bottom: calc(100% + 8px);
    background: rgba(15, 23, 42, 0.9);
    color: #fff;
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 6px;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity .2s ease;
}
.admin-notifications-widget:hover .admin-notif-drag-hint {
    opacity: 1;
}
</style>
@php
    $notifWidgetPos = class_exists(\App\Helpers\PlatformUx::class) ? \App\Helpers\PlatformUx::adminNotifWidgetPositionClasses() : 'bottom-6 right-6';
    $isTop = str_contains($notifWidgetPos, 'top-');
    $isLeft = str_contains($notifWidgetPos, 'left-');

    $sidePx = [];
    foreach (preg_split('/\s+/', trim($notifWidgetPos)) as $token) {
        if (preg_match('/^(top|bottom|left|right)-(\d+)$/', $token, $m)) {
            // Tailwind spacing scale: n * 4px (e.g. 6 => 24px).
            $sidePx[$m[1]] = ((int) $m[2]) * 4;
        }
    }
    $initialRight = $sidePx['right'] ?? null;
    $initialLeft = $sidePx['left'] ?? null;
    $initialTop = $sidePx['top'] ?? null;
    $initialBottom = $sidePx['bottom'] ?? null;
@endphp
<div
    id="admin-notifications-widget"
    class="admin-notifications-widget"
    data-initial-right="{{ $initialRight !== null ? $initialRight : '' }}"
    data-initial-left="{{ $initialLeft !== null ? $initialLeft : '' }}"
    data-initial-top="{{ $initialTop !== null ? $initialTop : '' }}"
    data-initial-bottom="{{ $initialBottom !== null ? $initialBottom : '' }}"
    data-panel-vertical="{{ $isTop ? 'below' : 'above' }}"
    data-panel-horizontal="{{ $isLeft ? 'left' : 'right' }}"
>
    <button type="button" onclick="window.toggleAdminNotificationsPanel && window.toggleAdminNotificationsPanel(event)" class="admin-notif-bell {{ $allNotifs->isNotEmpty() ? 'has-unread' : '' }}" title="اعلان‌های ادمین از پلتفرم">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        @if($allNotifs->isNotEmpty())
        <span class="admin-notif-badge">{{ $allNotifs->count() }}</span>
        @endif
    </button>
    <div class="admin-notif-drag-hint">برای جابه‌جایی بکشید</div>
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
    var b = w ? w.querySelector('.admin-notif-bell') : null;
    var positionKey = 'customer-admin-notif-widget-position-v1';
    var dragging = false;
    var moved = false;
    var startX = 0;
    var startY = 0;
    var startLeft = 0;
    var startTop = 0;

    function clamp(v, min, max) { return Math.max(min, Math.min(max, v)); }

    function setDockByPosition() {
        if (!w) return;
        var rect = w.getBoundingClientRect();
        var cx = rect.left + rect.width / 2;
        var cy = rect.top + rect.height / 2;
        w.setAttribute('data-panel-horizontal', cx > (window.innerWidth / 2) ? 'right' : 'left');
        w.setAttribute('data-panel-vertical', cy > (window.innerHeight / 2) ? 'above' : 'below');
    }

    function savePosition() {
        if (!w) return;
        var rect = w.getBoundingClientRect();
        localStorage.setItem(positionKey, JSON.stringify({ left: rect.left, top: rect.top }));
    }

    function restorePosition() {
        if (!w) return;
        try {
            var raw = localStorage.getItem(positionKey);
            if (!raw) return;
            var pos = JSON.parse(raw);
            if (typeof pos.left !== 'number' || typeof pos.top !== 'number') return;
            var maxLeft = Math.max(0, window.innerWidth - w.offsetWidth);
            var maxTop = Math.max(0, window.innerHeight - w.offsetHeight);
            var left = clamp(pos.left, 0, maxLeft);
            var top = clamp(pos.top, 0, maxTop);
            w.style.left = left + 'px';
            w.style.top = top + 'px';
            w.style.right = 'auto';
            w.style.bottom = 'auto';
            setDockByPosition();
        } catch (e) {}
    }

    function initializePositionFromData() {
        if (!w) return;
        var ir = w.getAttribute('data-initial-right');
        var il = w.getAttribute('data-initial-left');
        var it = w.getAttribute('data-initial-top');
        var ib = w.getAttribute('data-initial-bottom');
        w.style.right = ir !== '' ? ir + 'px' : 'auto';
        w.style.left = il !== '' ? il + 'px' : 'auto';
        w.style.top = it !== '' ? it + 'px' : 'auto';
        w.style.bottom = ib !== '' ? ib + 'px' : 'auto';
        if (ir === '' && il === '') w.style.right = '24px';
        if (it === '' && ib === '') w.style.bottom = '24px';
    }

    function applyPanelPosition() {
        if (!p || !w) return;
        var v = w.getAttribute('data-panel-vertical') || 'above';
        var h = w.getAttribute('data-panel-horizontal') || 'right';
        p.setAttribute('data-vertical', v);
        p.setAttribute('data-horizontal', h);
    }
    function toggle() {
        var e = arguments[0]; if (e) { e.preventDefault(); e.stopPropagation(); }
        if (moved) { moved = false; return; }
        if (!p || !w) return;
        applyPanelPosition();
        p.style.display = p.style.display === 'block' ? 'none' : 'block';
    }
    window.toggleAdminNotificationsPanel = toggle;
    if (p && w) {
        initializePositionFromData();
        restorePosition();
        applyPanelPosition();
        p.addEventListener('click', function(e){ e.stopPropagation(); });
        document.addEventListener('click', function(e){
            if (p.style.display === 'block' && !w.contains(e.target)) p.style.display = 'none';
        });

        if (b) {
            b.addEventListener('pointerdown', function(e){
                dragging = true;
                moved = false;
                b.setPointerCapture(e.pointerId);
                var rect = w.getBoundingClientRect();
                startX = e.clientX;
                startY = e.clientY;
                startLeft = rect.left;
                startTop = rect.top;
                e.preventDefault();
            });

            b.addEventListener('pointermove', function(e){
                if (!dragging) return;
                var dx = e.clientX - startX;
                var dy = e.clientY - startY;
                if (Math.abs(dx) > 4 || Math.abs(dy) > 4) moved = true;
                var maxLeft = Math.max(0, window.innerWidth - w.offsetWidth);
                var maxTop = Math.max(0, window.innerHeight - w.offsetHeight);
                var left = clamp(startLeft + dx, 0, maxLeft);
                var top = clamp(startTop + dy, 0, maxTop);
                w.style.left = left + 'px';
                w.style.top = top + 'px';
                w.style.right = 'auto';
                w.style.bottom = 'auto';
                setDockByPosition();
                applyPanelPosition();
            });

            b.addEventListener('pointerup', function(e){
                if (!dragging) return;
                dragging = false;
                b.releasePointerCapture(e.pointerId);
                if (moved) {
                    savePosition();
                }
            });
        }

        window.addEventListener('resize', function(){
            restorePosition();
            applyPanelPosition();
        });
    }
})();
</script>
@endif
