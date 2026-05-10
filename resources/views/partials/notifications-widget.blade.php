@php
    $localNotifs = \App\Helpers\SiteHelper::moduleEnabled('notifications')
        ? \App\Models\SiteNotification::active()->orderBy('order')->get()
        : collect();
    $platformNotifs = collect();
    if (\App\Helpers\SiteHelper::moduleEnabled('notifications') && class_exists(\App\Services\PlatformUxService::class)) {
        $platformNotifs = collect(app(\App\Services\PlatformUxService::class)->getPlatformNotifications());
    }
    $audiencePublic = fn($n) => ($n['audience'] ?? $n->audience ?? 'public') === 'public';
    $localNotifs = $localNotifs->filter(fn($n) => ($n->audience ?? 'public') === 'public');
    $platformNotifs = $platformNotifs->filter($audiencePublic);
    $rawItems = $localNotifs->map(fn($n) => [
        'id' => 'local_' . $n->id,
        'title' => $n->title,
        'body' => $n->body,
        'type' => $n->type,
        'link' => $n->link,
        'link_text' => $n->link_text,
        'image_url' => $n->image_path ? \App\Helpers\ImageHelper::url($n->image_path) : null,
        'position' => $n->position ?? 'top',
        'image_position' => $n->image_position ?? 'right',
        'priority' => 'normal',
        'created_at' => $n->created_at?->toIso8601String(),
    ])->concat($platformNotifs->map(fn($n) => [
        'id' => isset($n['id']) ? 'platform_' . $n['id'] : 'platform_' . md5(($n['title'] ?? '') . ($n['body'] ?? '') . ($n['link'] ?? '')),
        'audience' => $n['audience'] ?? 'public',
        'title' => $n['title'] ?? '',
        'body' => $n['body'] ?? null,
        'type' => $n['type'] ?? 'info',
        'link' => $n['link'] ?? null,
        'link_text' => $n['link_text'] ?? null,
        'image_url' => $n['image_url'] ?? null,
        'position' => $n['position'] ?? 'top',
        'image_position' => $n['image_position'] ?? 'right',
        'priority' => $n['priority'] ?? 'normal',
        'created_at' => $n['created_at'] ?? null,
    ]))->values()->all();
    $allNotifs = collect(\App\Helpers\NotificationUi::sortToolbarItems($rawItems));
    $typeColors = ['info' => '#2563eb', 'success' => '#16a34a', 'warning' => '#d97706', 'danger' => '#dc2626', 'promo' => '#7c3aed', 'ad' => '#4f46e5'];
    $showWidget = \App\Helpers\SiteHelper::moduleEnabled('notifications');
@endphp
@if($showWidget)
<div id="notifications-widget" class="pub-notif-widget">
    <button type="button" id="notifications-bell-btn" class="pub-notif-widget__bell" onclick="window.toggleNotificationsPanel(event)" aria-label="اعلان‌ها">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="pub-notif-widget__bell-icon">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        <span id="notifications-badge" class="pub-notif-widget__badge"></span>
    </button>
    <div id="notifications-panel" class="pub-notif-widget__panel">
        <div class="pub-notif-widget__head">اعلان‌ها</div>
        <div id="notifications-list">
            @if($allNotifs->isEmpty())
            <div class="pub-notif-widget__empty">اعلان جدیدی وجود ندارد.</div>
            @else
            @foreach($allNotifs as $n)
            @php
                $accent = $typeColors[$n['type']] ?? $typeColors['info'];
                $isFresh = \App\Helpers\NotificationUi::isFresh($n['created_at'] ?? null);
            @endphp
            <div class="notif-item pub-notif-item {{ $isFresh ? 'pub-notif-item--fresh' : '' }}" data-id="{{ $n['id'] }}" style="--notif-accent: {{ $accent }};">
                @if($n['image_url'] && in_array($n['image_position'] ?? 'right', ['left']))
                <img src="{{ $n['image_url'] }}" alt="" class="pub-notif-item__thumb">
                @endif
                <div class="pub-notif-item__body">
                    <div class="pub-notif-item__title-row">
                        @if($isFresh)
                            <span class="pub-notif-item__new">جدید</span>
                        @endif
                        @if(($n['priority'] ?? 'normal') === 'urgent')
                            <span class="pub-notif-item__pill pub-notif-item__pill--urgent">فوری</span>
                        @elseif(($n['priority'] ?? 'normal') === 'high')
                            <span class="pub-notif-item__pill pub-notif-item__pill--high">مهم</span>
                        @endif
                        <span class="pub-notif-item__title">{{ $n['title'] ?: 'اعلان' }}</span>
                    </div>
                    @if($n['body'])
                    <div class="pub-notif-item__text">{{ Str::limit(strip_tags($n['body']), 100) }}</div>
                    @endif
                    @if($n['link'])
                    <a href="{{ $n['link'] }}" target="_blank" rel="noopener" class="pub-notif-item__link">{{ $n['link_text'] ?: 'مشاهده' }}</a>
                    @endif
                </div>
                @if($n['image_url'] && in_array($n['image_position'] ?? 'right', ['right']))
                <img src="{{ $n['image_url'] }}" alt="" class="pub-notif-item__thumb">
                @endif
            </div>
            @endforeach
            @endif
        </div>
    </div>
</div>
<style>
.pub-notif-widget {
    position: fixed;
    bottom: 1.5rem;
    inset-inline-end: 1.5rem;
    z-index: 99999;
    font-family: Tahoma, "Segoe UI", system-ui, sans-serif;
}
.pub-notif-widget__bell {
    position: relative;
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    background: linear-gradient(145deg, #4f46e5, #4338ca);
    color: #fff;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 22px rgba(67, 56, 202, 0.35);
    transition: transform 0.18s ease, box-shadow 0.18s ease;
}
.pub-notif-widget__bell:hover { transform: scale(1.06); box-shadow: 0 10px 28px rgba(67, 56, 202, 0.42); }
.pub-notif-widget__badge {
    position: absolute;
    top: -4px;
    inset-inline-end: -4px;
    min-width: 1.25rem;
    height: 1.25rem;
    border-radius: 9999px;
    background: #dc2626;
    color: #fff;
    font-size: 0.68rem;
    font-weight: 800;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
}
.pub-notif-widget__panel {
    display: none;
    position: absolute;
    bottom: calc(100% + 0.5rem);
    inset-inline-end: 0;
    width: min(360px, calc(100vw - 2rem));
    max-height: 70vh;
    overflow-y: auto;
    background: #fff;
    border-radius: 0.75rem;
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.14);
    border: 1px solid #e2e8f0;
    z-index: 100000;
}
.pub-notif-widget__head {
    padding: 0.7rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    font-weight: 700;
    font-size: 0.9rem;
    color: #0f172a;
    position: sticky;
    top: 0;
    background: #fff;
    z-index: 1;
}
.pub-notif-widget__empty { padding: 1.5rem 1rem; color: #64748b; font-size: 0.875rem; text-align: center; }
@keyframes pub-notif-shake {
    0%,100%{ transform: rotate(0deg); }
    15%{ transform: rotate(-7deg); }
    30%{ transform: rotate(7deg); }
    45%{ transform: rotate(-4deg); }
    60%{ transform: rotate(4deg); }
    75%{ transform: rotate(0deg); }
}
.pub-notif-widget__bell-icon { animation: pub-notif-shake 2.4s ease-in-out infinite; }
.pub-notif-item {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    padding: 0.65rem 0.85rem;
    margin: 0.4rem 0.5rem;
    border-radius: 0.6rem;
    border: 1px solid #f1f5f9;
    background: #fafafa;
    border-inline-start: 3px solid var(--notif-accent, #4f46e5);
    cursor: pointer;
    transition: background 0.15s ease, box-shadow 0.15s ease;
}
.pub-notif-item:hover { background: #f8fafc; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06); }
.pub-notif-item--fresh {
    background: linear-gradient(180deg, #f5f3ff 0%, #faf5ff 100%);
    border-color: #e9d5ff;
    box-shadow: 0 2px 12px rgba(124, 58, 237, 0.08);
}
.pub-notif-item__thumb { width: 44px; height: 44px; object-fit: cover; border-radius: 0.45rem; flex-shrink: 0; }
.pub-notif-item__body { flex: 1; min-width: 0; }
.pub-notif-item__title-row { display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem; }
.pub-notif-item__title { font-size: 0.84rem; font-weight: 700; color: #1e293b; line-height: 1.35; }
.pub-notif-item__text { font-size: 0.78rem; color: #64748b; margin-top: 0.25rem; line-height: 1.45; }
.pub-notif-item__link { display: inline-block; margin-top: 0.35rem; font-size: 0.75rem; font-weight: 600; color: var(--notif-accent, #4f46e5); text-decoration: none; }
.pub-notif-item__link:hover { text-decoration: underline; }
.pub-notif-item__pill { font-size: 0.62rem; font-weight: 800; padding: 0.12rem 0.4rem; border-radius: 0.25rem; }
.pub-notif-item__pill--urgent { background: #fef2f2; color: #b91c1c; }
.pub-notif-item__pill--high { background: #fffbeb; color: #b45309; }
.pub-notif-item__new { font-size: 0.62rem; font-weight: 800; color: #6d28d9; background: #ede9fe; padding: 0.1rem 0.35rem; border-radius: 0.25rem; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
(function(){
    var STORAGE_KEY = 'notifications_seen_ids';
    var ids = {{ json_encode($allNotifs->pluck('id')->values()->all()) }};
    var hasItems = ids.length > 0;
    function getSeenIds() {
        try {
            var s = localStorage.getItem(STORAGE_KEY);
            return s ? JSON.parse(s) : [];
        } catch(e) { return []; }
    }
    function setSeenIds(arr) {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(arr)); } catch(e) {}
    }
    function markAllSeen() {
        setSeenIds(ids.slice());
        updateBadge();
    }
    function getUnreadCount() {
        var seen = getSeenIds();
        return ids.filter(function(id){ return seen.indexOf(id) === -1; }).length;
    }
    function updateBadge() {
        var badge = document.getElementById('notifications-badge');
        if (!badge) return;
        var c = hasItems ? getUnreadCount() : 0;
        badge.textContent = c > 0 ? (c > 99 ? '99+' : c) : '';
        badge.style.display = c > 0 ? 'flex' : 'none';
    }
    function togglePanel(e) {
        if (e) { e.preventDefault(); e.stopPropagation(); }
        var panel = document.getElementById('notifications-panel');
        if (!panel) return;
        var open = panel.style.display === 'block';
        panel.style.display = open ? 'none' : 'block';
        if (!open) markAllSeen();
    }
    window.toggleNotificationsPanel = function(e){ togglePanel(e); };
    var panel = document.getElementById('notifications-panel');
    var widget = document.getElementById('notifications-widget');
    if (panel && widget) {
        panel.addEventListener('click', function(e) { e.stopPropagation(); });
        document.addEventListener('click', function(e) {
            if (panel.style.display === 'block' && !widget.contains(e.target)) {
                panel.style.display = 'none';
            }
        });
    }
    document.querySelectorAll('.notif-item').forEach(function(el) {
        el.addEventListener('click', function() { markAllSeen(); });
    });
    updateBadge();
})();
});
</script>
@endif
