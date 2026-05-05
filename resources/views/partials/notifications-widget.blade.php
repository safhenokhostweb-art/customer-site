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
    $priorityOrder = ['urgent' => 1, 'high' => 2, 'normal' => 3, 'low' => 4];
    $allNotifs = $localNotifs->map(fn($n) => [
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
    ])->concat($platformNotifs->map(fn($n) => [
        'id' => 'platform_' . md5(($n['title'] ?? '') . ($n['body'] ?? '') . ($n['link'] ?? '')),
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
    ]))->sortBy(fn($n) => $priorityOrder[$n['priority'] ?? 'normal'] ?? 3)->values();
    $typeColors = ['info' => '#3b82f6', 'success' => '#22c55e', 'warning' => '#f59e0b', 'danger' => '#ef4444'];
    $showWidget = \App\Helpers\SiteHelper::moduleEnabled('notifications');
@endphp
@if($showWidget)
<div id="notifications-widget" style="position:fixed;bottom:1.5rem;right:1.5rem;z-index:99999;">
    <button type="button" id="notifications-bell-btn" onclick="window.toggleNotificationsPanel(event)" aria-label="اعلان‌ها" style="
        position:relative;width:3rem;height:3rem;border-radius:50%;
        background:{{ $typeColors['info'] ?? '#3b82f6' }};
        color:#fff;border:none;cursor:pointer;pointer-events:auto;
        display:flex;align-items:center;justify-content:center;
        box-shadow:0 4px 12px rgba(59,130,246,0.4);
        transition:transform 0.2s;
    " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="notif-bell-icon">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        <span id="notifications-badge" style="
            position:absolute;top:-4px;right:-4px;
            min-width:1.25rem;height:1.25rem;border-radius:9999px;
            background:#ef4444;color:#fff;font-size:0.7rem;font-weight:700;
            display:flex;align-items:center;justify-content:center;padding:0 4px;
        "></span>
    </button>
    <div id="notifications-panel" style="
        display:none;position:absolute;bottom:calc(100% + 0.5rem);right:0;
        width:min(340px, calc(100vw - 2rem));max-height:70vh;overflow-y:auto;
        background:#fff;border-radius:0.5rem;box-shadow:0 10px 40px rgba(0,0,0,0.15);
        border:1px solid #e5e7eb;z-index:100000;
    ">
        <div style="padding:0.75rem 1rem;border-bottom:1px solid #e5e7eb;font-weight:600;font-size:0.95rem;">اعلان‌ها</div>
        <div id="notifications-list">
            @if($allNotifs->isEmpty())
            <div style="padding:1.5rem 1rem;color:#6b7280;font-size:0.9rem;text-align:center;">اعلان جدیدی وجود ندارد.</div>
            @else
            @foreach($allNotifs as $n)
            <div class="notif-item" data-id="{{ $n['id'] }}" style="
                padding:0.75rem 1rem;border-bottom:1px solid #f3f4f6;
                display:flex;align-items:flex-start;gap:0.75rem;
            ">
                @if($n['image_url'] && in_array($n['image_position'] ?? 'right', ['left']))
                <img src="{{ $n['image_url'] }}" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:0.375rem;flex-shrink:0;">
                @endif
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:600;font-size:0.9rem;color:#111827;display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                        @if(($n['priority'] ?? 'normal') === 'urgent')
                            <span style="font-size:0.7rem;background:#fef2f2;color:#b91c1c;padding:0.15rem 0.4rem;border-radius:0.25rem;">فوری</span>
                        @elseif(($n['priority'] ?? 'normal') === 'high')
                            <span style="font-size:0.7rem;background:#fffbeb;color:#b45309;padding:0.15rem 0.4rem;border-radius:0.25rem;">مهم</span>
                        @endif
                        {{ $n['title'] ?: 'اعلان' }}
                    </div>
                    @if($n['body'])
                    <div style="font-size:0.8rem;color:#6b7280;margin-top:0.2rem;">{{ Str::limit($n['body'], 80) }}</div>
                    @endif
                    @if($n['link'])
                    <a href="{{ $n['link'] }}" target="_blank" rel="noopener" style="color:{{ $typeColors[$n['type']] ?? '#3b82f6' }};font-size:0.8rem;margin-top:0.25rem;display:inline-block;">{{ $n['link_text'] ?: 'مشاهده' }}</a>
                    @endif
                </div>
                @if($n['image_url'] && in_array($n['image_position'] ?? 'right', ['right']))
                <img src="{{ $n['image_url'] }}" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:0.375rem;flex-shrink:0;">
                @endif
            </div>
            @endforeach
            @endif
        </div>
    </div>
</div>
<style>
@keyframes notif-bell-shake {
    0%,100%{ transform: rotate(0deg); }
    10%,30%,50%,70%,90%{ transform: rotate(-8deg); }
    20%,40%,60%,80%{ transform: rotate(8deg); }
}
.notif-bell-icon { animation: notif-bell-shake 2s ease-in-out infinite; }
#notifications-widget { font-family: inherit; }
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
