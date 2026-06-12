@php
    $platformNotifs = collect();
    if (\App\Helpers\SiteHelper::moduleEnabled('notifications') && class_exists(\App\Services\PlatformUxService::class)) {
        $platformNotifs = collect(app(\App\Services\PlatformUxService::class)->getPlatformNotifications())
            ->filter(fn($n) => ($n['audience'] ?? 'public') === 'admin');
    }
    $rawAdmin = $platformNotifs->map(fn($n) => [
        'id' => isset($n['id']) ? 'platform_' . $n['id'] : 'platform_' . md5(($n['title'] ?? '') . ($n['body'] ?? '')),
        'title' => $n['title'] ?? '',
        'body' => $n['body'] ?? null,
        'type' => $n['type'] ?? 'info',
        'link' => $n['link'] ?? null,
        'link_text' => $n['link_text'] ?? null,
        'image_url' => $n['image_url'] ?? null,
        'priority' => $n['priority'] ?? 'normal',
        'created_at' => $n['created_at'] ?? null,
    ])->values()->all();
    $allNotifs = collect(\App\Helpers\NotificationUi::sortToolbarItems($rawAdmin));
    $adminTypeColors = ['info' => '#2563eb', 'success' => '#16a34a', 'warning' => '#d97706', 'danger' => '#dc2626'];
@endphp
@if(\App\Helpers\SiteHelper::moduleEnabled('notifications'))
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
    <button type="button" onclick="window.toggleAdminNotificationsPanel && window.toggleAdminNotificationsPanel(event)" class="admin-notif-bell {{ $allNotifs->isNotEmpty() ? 'has-unread' : '' }}" title="ط§ط¹ظ„ط§ظ†â€Œظ‡ط§غŒ ط§ط¯ظ…غŒظ† ط§ط² ظ¾ظ„طھظپط±ظ…">
        <x-core-ui::icon name="bell" style="width:20px;height:20px" />
        @if($allNotifs->isNotEmpty())
        <span class="admin-notif-badge">{{ $allNotifs->count() }}</span>
        @endif
    </button>
    <div class="admin-notif-drag-hint">ط¨ط±ط§غŒ ط¬ط§ط¨ظ‡â€Œط¬ط§غŒغŒ ط¨ع©ط´غŒط¯</div>
    <div id="admin-notifications-panel" class="admin-notif-panel">
        <div class="admin-notif-panel__head">ط§ط¹ظ„ط§ظ†â€Œظ‡ط§غŒ ظ¾ظ„طھظپط±ظ…</div>
        @if($allNotifs->isEmpty())
        <div class="admin-notif-panel__empty">
            ط§ط¹ظ„ط§ظ†غŒ ظˆط¬ظˆط¯ ظ†ط¯ط§ط±ط¯.<br>
            <a href="{{ route('admin.license.refresh-config.get') }}" class="text-indigo-600 hover:underline mt-2 inline-block font-medium">ط¨ط§ط²ط®ظˆط§ظ†غŒ طھظ†ط¸غŒظ…ط§طھ ظˆ ط§ط¹ظ„ط§ظ†â€Œظ‡ط§</a><br>
            <span class="admin-notif-panel__hint">ظ„ط§غŒط³ظ†ط³ ط§غŒظ† ط³ط§غŒطھ ط¯ط± ظ¾ظ„طھظپط±ظ… ط¨ط§غŒط¯ ط¨ظ‡ ظ‡ظ…ط§ظ† ط³ط§غŒطھغŒ ظ…طھطµظ„ ط¨ط§ط´ط¯ ع©ظ‡ ط§ط¹ظ„ط§ظ† ط¨ط±ط§غŒط´ ط«ط¨طھ ط´ط¯ظ‡ ط§ط³طھ.</span>
        </div>
        @else
        @foreach($allNotifs as $n)
        @php
            $acc = $adminTypeColors[$n['type'] ?? 'info'] ?? $adminTypeColors['info'];
            $fresh = \App\Helpers\NotificationUi::isFresh($n['created_at'] ?? null);
        @endphp
        <div class="admin-notif-card {{ $fresh ? 'admin-notif-card--fresh' : '' }}" style="--admin-notif-accent: {{ $acc }};">
            @if(!empty($n['image_url']))
            <div class="admin-notif-card__imgwrap"><img src="{{ $n['image_url'] }}" alt="" class="admin-notif-card__img"></div>
            @endif
            <div class="admin-notif-card__row">
                @if($fresh)<span class="admin-notif-card__new">ط¬ط¯غŒط¯</span>@endif
                @if(($n['priority'] ?? 'normal') === 'urgent')
                    <span class="admin-notif-card__pill admin-notif-card__pill--urgent">ظپظˆط±غŒ</span>
                @elseif(($n['priority'] ?? 'normal') === 'high')
                    <span class="admin-notif-card__pill admin-notif-card__pill--high">ظ…ظ‡ظ…</span>
                @endif
            </div>
            <div class="admin-notif-card__title">{{ $n['title'] ?: 'ط§ط¹ظ„ط§ظ†' }}</div>
            @if($n['body'])<div class="admin-notif-card__body">{{ Str::limit(strip_tags($n['body']), 100) }}</div>@endif
            @if($n['link'])<a href="{{ $n['link'] }}" target="_blank" rel="noopener" class="admin-notif-card__link">{{ $n['link_text'] ?: 'ظ…ط´ط§ظ‡ط¯ظ‡' }}</a>@endif
        </div>
        @endforeach
        @endif
    </div>
</div>
<style>
.admin-notif-panel {
    display: none;
    position: absolute;
    width: min(340px, 90vw);
    max-height: 60vh;
    overflow-y: auto;
    background: #fff;
    border-radius: 0.75rem;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.14);
    border: 1px solid #e2e8f0;
    z-index: 99999;
    font-family: Tahoma, "Segoe UI", system-ui, sans-serif;
}
.admin-notif-panel__head {
    padding: 0.65rem 0.85rem;
    border-bottom: 1px solid #f1f5f9;
    font-weight: 700;
    font-size: 0.875rem;
    color: #0f172a;
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 1;
}
.admin-notif-panel__empty { padding: 1.25rem 0.85rem; color: #64748b; font-size: 0.875rem; text-align: center; line-height: 1.5; }
.admin-notif-panel__hint { font-size: 0.75rem; margin-top: 0.5rem; display: block; color: #94a3b8; }
.admin-notif-card {
    margin: 0.45rem 0.5rem;
    padding: 0.65rem 0.75rem;
    border-radius: 0.55rem;
    border: 1px solid #f1f5f9;
    border-inline-start: 3px solid var(--admin-notif-accent, #4f46e5);
    background: #fafafa;
}
.admin-notif-card--fresh {
    background: linear-gradient(180deg, #f5f3ff 0%, #faf5ff 100%);
    border-color: #e9d5ff;
    box-shadow: 0 2px 10px rgba(124, 58, 237, 0.07);
}
.admin-notif-card__imgwrap { margin-bottom: 0.45rem; }
.admin-notif-card__img { max-width: 100%; max-height: 72px; object-fit: contain; border-radius: 0.35rem; }
.admin-notif-card__row { display: flex; flex-wrap: wrap; gap: 0.3rem; align-items: center; margin-bottom: 0.25rem; }
.admin-notif-card__new { font-size: 0.62rem; font-weight: 800; color: #6d28d9; background: #ede9fe; padding: 0.1rem 0.35rem; border-radius: 0.25rem; }
.admin-notif-card__pill { font-size: 0.6rem; font-weight: 800; padding: 0.1rem 0.35rem; border-radius: 0.25rem; }
.admin-notif-card__pill--urgent { background: #fef2f2; color: #b91c1c; }
.admin-notif-card__pill--high { background: #fffbeb; color: #b45309; }
.admin-notif-card__title { font-size: 0.84rem; font-weight: 700; color: #1e293b; line-height: 1.35; }
.admin-notif-card__body { font-size: 0.78rem; color: #64748b; margin-top: 0.25rem; line-height: 1.45; }
.admin-notif-card__link { display: inline-block; margin-top: 0.35rem; font-size: 0.75rem; font-weight: 600; color: var(--admin-notif-accent, #4f46e5); }
#admin-notifications-panel[data-vertical="above"] { bottom: calc(100% + 0.5rem); top: auto; }
#admin-notifications-panel[data-vertical="below"] { top: calc(100% + 0.5rem); bottom: auto; }
#admin-notifications-panel[data-horizontal="right"] { inset-inline-end: 0; inset-inline-start: auto; }
#admin-notifications-panel[data-horizontal="left"] { inset-inline-start: 0; inset-inline-end: auto; }
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


