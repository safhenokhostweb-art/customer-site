@php
    $position = \App\Models\Setting::get('notification_bell_position', 'bottom-right');
    $unreadCount = auth()->user()->notifications()->unread()->count();
    $positions = [
        'bottom-right' => ['bottom' => '1.5rem', 'right'  => '1.5rem'],
        'bottom-left'  => ['bottom' => '1.5rem', 'left'   => '1.5rem'],
        'top-right'    => ['top'    => '1.5rem', 'right'  => '1.5rem'],
        'top-left'     => ['top'    => '1.5rem', 'left'   => '1.5rem'],
    ];
    $pos = $positions[$position] ?? $positions['bottom-right'];
    $initStyle = collect($pos)->map(fn($v,$k) => "$k:$v")->implode(';');
@endphp
@if(\App\Helpers\SiteHelper::moduleEnabled('notifications'))
<style>
/* ── Bell shake ── */
@keyframes account-bell-shake {
    0%,100%{transform:rotate(0)}
    10%,30%{transform:rotate(-12deg)}
    20%,40%{transform:rotate(12deg)}
    50%{transform:rotate(0)}
}
/* ── Glow pulse rings ── */
@keyframes bell-ring-pulse {
    0%   { transform:scale(1);   opacity:0.55; }
    100% { transform:scale(2.8); opacity:0; }
}
.bell-glow-ring {
    position:absolute;inset:0;border-radius:50%;pointer-events:none;
    background:radial-gradient(circle, rgba(99,102,241,0.55) 0%, rgba(99,102,241,0) 68%);
    animation:bell-ring-pulse 2.6s ease-out infinite;
}
.bell-glow-ring:nth-child(2){ animation-delay:0.85s; }
.bell-glow-ring:nth-child(3){ animation-delay:1.7s; }

/* ── Drag hint tooltip (hover) ── */
.bell-drag-tooltip {
    position:absolute;
    bottom:calc(100% + 0.6rem);
    right:50%;transform:translateX(50%) translateY(4px);
    background:rgba(15,23,42,0.88);
    backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
    color:#f8fafc;font-size:0.65rem;font-weight:500;font-family:Vazirmatn,Tahoma,sans-serif;
    padding:0.3rem 0.65rem;border-radius:0.45rem;
    white-space:nowrap;pointer-events:none;
    opacity:0;transition:opacity 0.22s,transform 0.22s;
    box-shadow:0 4px 12px rgba(0,0,0,0.25);
}
.bell-drag-tooltip::after {
    content:'';position:absolute;top:100%;left:50%;transform:translateX(-50%);
    border:5px solid transparent;border-top-color:rgba(15,23,42,0.88);
}
#account-notification-bell:not(.dragging):hover .bell-drag-tooltip {
    opacity:1;transform:translateX(50%) translateY(0);
}

/* ── First-visit hint label ── */
.bell-first-hint {
    position:absolute;
    right:calc(100% + 0.6rem);top:50%;transform:translateY(-50%);
    display:flex;align-items:center;gap:0.3rem;
    background:rgba(79,70,229,0.92);
    backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);
    color:#fff;font-size:0.68rem;font-weight:600;font-family:Vazirmatn,Tahoma,sans-serif;
    padding:0.3rem 0.6rem;border-radius:0.5rem;
    white-space:nowrap;pointer-events:none;
    box-shadow:0 4px 14px rgba(79,70,229,0.4);
    animation:hint-appear 0.4s ease both;
}
.bell-first-hint::after {
    content:'';position:absolute;right:-8px;top:50%;transform:translateY(-50%);
    border:5px solid transparent;border-left-color:rgba(79,70,229,0.92);
}
@keyframes hint-appear {
    from{opacity:0;transform:translateY(-50%) translateX(-6px)}
    to  {opacity:1;transform:translateY(-50%) translateX(0)}
}

/* ── Bell states ── */
.account-notification-bell-link.has-unread { animation: account-bell-shake 2s ease-in-out infinite; }
.account-notification-bell-link.has-unread:hover,
.account-notification-bell-link.has-unread.dragging { animation: none; }
#account-notification-bell { cursor:grab;user-select:none; }
#account-notification-bell.dragging { cursor:grabbing; }
#account-notification-bell.dragging .bell-glow-ring { animation:none; opacity:0; }
</style>

<div id="account-notification-bell"
     style="position:fixed;{{ $initStyle }};z-index:99999;touch-action:none;width:3rem;height:3rem;"
     data-notif-url="{{ route('account.notifications.index') }}">

    {{-- Glow rings --}}
    <div class="bell-glow-ring"></div>
    <div class="bell-glow-ring"></div>
    <div class="bell-glow-ring"></div>

    {{-- Bell button --}}
    <div class="account-notification-bell-link {{ $unreadCount > 0 ? 'has-unread' : '' }}" style="
        position:relative;z-index:1;display:flex;align-items:center;justify-content:center;
        width:3rem;height:3rem;border-radius:50%;
        background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;
        box-shadow:0 4px 16px rgba(79,70,229,0.5),0 0 0 3px rgba(99,102,241,0.18);
        transition:box-shadow 0.2s,transform 0.15s;
    ">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        @if($unreadCount > 0)
        <span style="
            position:absolute;top:-5px;right:-5px;min-width:1.3rem;height:1.3rem;border-radius:9999px;
            background:#ef4444;color:#fff;font-size:0.68rem;font-weight:700;
            display:flex;align-items:center;justify-content:center;padding:0 4px;
            box-shadow:0 0 0 2px #fff;
        ">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
    </div>

    {{-- Hover tooltip --}}
    <div class="bell-drag-tooltip">🖱️ بکشید تا جابجا کنید</div>

    {{-- First-visit hint (shown by JS for 4s) --}}
    <div class="bell-first-hint" id="bell-first-hint" style="display:none">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 9l4 4 4-4"/><path d="M9 13V3"/><path d="M19 15l-4-4-4 4"/><path d="M15 11v10"/></svg>
        قابل جابجایی
    </div>
</div>

<script>
(function(){
    var el       = document.getElementById('account-notification-bell');
    var firstHint = document.getElementById('bell-first-hint');
    if (!el) return;

    var notifUrl = el.dataset.notifUrl;
    var stored   = null;
    try { stored = JSON.parse(localStorage.getItem('notif_bell_pos')); } catch(e){}

    /* Restore saved position */
    if (stored && typeof stored.x === 'number' && typeof stored.y === 'number') {
        var size = 56;
        var maxX = window.innerWidth  - size;
        var maxY = window.innerHeight - size;
        el.style.left   = Math.min(Math.max(0, stored.x), maxX) + 'px';
        el.style.top    = Math.min(Math.max(0, stored.y), maxY) + 'px';
        el.style.right  = 'auto';
        el.style.bottom = 'auto';
    }

    /* Show first-visit hint once */
    if (firstHint && !localStorage.getItem('notif_bell_hint_seen')) {
        setTimeout(function(){
            firstHint.style.display = 'flex';
            setTimeout(function(){
                firstHint.style.transition = 'opacity 0.5s';
                firstHint.style.opacity = '0';
                setTimeout(function(){ firstHint.style.display = 'none'; }, 550);
            }, 3800);
        }, 800);
        localStorage.setItem('notif_bell_hint_seen', '1');
    }

    /* Drag logic */
    var dragging = false;
    var startX, startY, origX, origY, moved;

    function getElPos() {
        var r = el.getBoundingClientRect();
        return { x: r.left, y: r.top };
    }

    function onStart(cx, cy) {
        dragging = true;
        moved    = false;
        var p    = getElPos();
        origX    = p.x;
        origY    = p.y;
        startX   = cx;
        startY   = cy;
        el.style.left   = origX + 'px';
        el.style.top    = origY + 'px';
        el.style.right  = 'auto';
        el.style.bottom = 'auto';
        el.classList.add('dragging');
        if (firstHint) firstHint.style.display = 'none';
        document.body.style.userSelect = 'none';
    }

    function onMove(cx, cy) {
        if (!dragging) return;
        var dx = cx - startX;
        var dy = cy - startY;
        if (Math.abs(dx) > 4 || Math.abs(dy) > 4) moved = true;
        var size = el.offsetWidth || 48;
        var nx   = Math.min(Math.max(0, origX + dx), window.innerWidth  - size);
        var ny   = Math.min(Math.max(0, origY + dy), window.innerHeight - size);
        el.style.left = nx + 'px';
        el.style.top  = ny + 'px';
    }

    function onEnd(cx, cy) {
        if (!dragging) return;
        dragging = false;
        el.classList.remove('dragging');
        document.body.style.userSelect = '';
        if (!moved && notifUrl) {
            window.location.href = notifUrl;
            return;
        }
        var r = el.getBoundingClientRect();
        try { localStorage.setItem('notif_bell_pos', JSON.stringify({ x: r.left, y: r.top })); } catch(e){}
    }

    /* Mouse */
    el.addEventListener('mousedown', function(e){ e.preventDefault(); onStart(e.clientX, e.clientY); });
    document.addEventListener('mousemove', function(e){ onMove(e.clientX, e.clientY); });
    document.addEventListener('mouseup',   function(e){ onEnd(e.clientX, e.clientY); });

    /* Touch */
    el.addEventListener('touchstart', function(e){ var t=e.touches[0]; onStart(t.clientX,t.clientY); }, {passive:true});
    document.addEventListener('touchmove',  function(e){ if(!dragging)return; e.preventDefault(); var t=e.touches[0]; onMove(t.clientX,t.clientY); }, {passive:false});
    document.addEventListener('touchend',   function(e){ var t=e.changedTouches[0]; onEnd(t.clientX,t.clientY); });
})();
</script>
@endif
