@php
    $position = \App\Models\Setting::get('notification_bell_position', 'bottom-right');
    $unreadCount = auth()->user()->notifications()->unread()->count();
    $inboxRecent = \App\Models\UserNotification::where('user_id', auth()->id())->latest()->limit(5)->get();
    $platformPublic = collect();
    if (\App\Helpers\SiteHelper::moduleEnabled('notifications') && class_exists(\App\Services\PlatformUxService::class)) {
        $platformPublic = collect(app(\App\Services\PlatformUxService::class)->getPlatformNotifications())
            ->filter(fn ($n) => ($n['audience'] ?? 'public') === 'public');
    }
    $platformItems = \App\Helpers\NotificationUi::sortToolbarItems(
        $platformPublic->map(fn ($n) => [
            'id'         => isset($n['id']) ? 'p_' . $n['id'] : 'p_' . md5(($n['title'] ?? '') . ($n['body'] ?? '')),
            'title'      => $n['title'] ?? '',
            'body'       => $n['body'] ?? null,
            'type'       => $n['type'] ?? 'info',
            'link'       => $n['link'] ?? null,
            'link_text'  => $n['link_text'] ?? null,
            'priority'   => $n['priority'] ?? 'normal',
            'created_at' => $n['created_at'] ?? null,
        ])->values()->all()
    );
    $typeAccent = ['info' => '#2563eb', 'success' => '#16a34a', 'warning' => '#d97706', 'danger' => '#dc2626'];
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

/* ── Notification panel ── */
#acct-bell-panel {
    position:absolute;
    width:min(22rem, calc(100vw - 2rem));
    max-height:min(70vh, 28rem);
    overflow-y:auto;
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:0.75rem;
    box-shadow:0 18px 48px rgba(15,23,42,0.15);
    z-index:100;
    font-family:Vazirmatn,Tahoma,sans-serif;
    cursor:default;
}
#acct-bell-panel[hidden]{ display:none !important; }

.acct-bp__head {
    display:flex;align-items:center;justify-content:space-between;
    padding:0.65rem 0.85rem;
    border-bottom:1px solid #f1f5f9;
    font-weight:700;font-size:0.875rem;color:#0f172a;
    position:sticky;top:0;background:#fff;z-index:1;
}
.acct-bp__link-all { font-size:0.75rem;font-weight:600;color:#4f46e5;text-decoration:none; }
.acct-bp__link-all:hover { text-decoration:underline; }
.acct-bp__empty { padding:1.5rem;text-align:center;color:#64748b;font-size:0.875rem; }
.acct-bp__section-title {
    padding:0.5rem 0.85rem 0.25rem;
    font-size:0.7rem;font-weight:700;color:#94a3b8;letter-spacing:0.04em;
}
.acct-bp__list { list-style:none;margin:0;padding:0 0 0.5rem; }
.acct-bp__row {
    display:flex;gap:0.5rem;align-items:flex-start;
    padding:0.55rem 0.85rem;margin:0 0.5rem 0.35rem;
    border-radius:0.5rem;
    background:#fafafa;border:1px solid #f1f5f9;
    text-decoration:none;color:inherit;position:relative;
}
.acct-bp__row.is-new { background:#f5f3ff;border-color:#ddd6fe; }
.acct-bp__dot { width:6px;height:6px;border-radius:50%;margin-top:0.35rem;flex-shrink:0; }
.acct-bp__row-text { min-width:0;flex:1; }
.acct-bp__title { display:block;font-size:0.8125rem;font-weight:600;color:#1e293b;line-height:1.35; }
.acct-bp__meta  { display:block;font-size:0.75rem;color:#64748b;margin-top:0.2rem;line-height:1.4; }
.acct-bp__cta   { display:inline-block;margin-top:0.35rem;font-size:0.72rem;font-weight:600;color:#4f46e5; }
.acct-bp__new-pill {
    position:absolute;top:0.35rem;inset-inline-end:0.35rem;
    font-size:0.62rem;font-weight:800;color:#6d28d9;
    background:#ede9fe;padding:0.1rem 0.35rem;border-radius:0.25rem;
}
</style>

<div id="account-notification-bell"
     style="position:fixed;{{ $initStyle }};z-index:99999;touch-action:none;width:3rem;height:3rem;">

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
        <x-core-ui::icon name="bell" style="width:22px;height:22px" />
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
        <x-core-ui::icon name="bell" />
        قابل جابجایی
    </div>

    {{-- Inline notification panel --}}
    <div id="acct-bell-panel" hidden>
        <div class="acct-bp__head">
            <span>اعلان‌ها</span>
            <a href="{{ route('account.notifications.index') }}" class="acct-bp__link-all">همه پیام‌ها</a>
        </div>

        @if($inboxRecent->isEmpty() && count($platformItems) === 0)
            <div class="acct-bp__empty">موردی برای نمایش نیست.</div>
        @else
            @if($inboxRecent->isNotEmpty())
                <div class="acct-bp__section-title">صندوق ورودی</div>
                <ul class="acct-bp__list">
                    @foreach($inboxRecent as $n)
                    <li>
                        <a href="{{ route('account.notifications.index') }}" class="acct-bp__row {{ $n->read_at ? '' : 'is-new' }}">
                            <span class="acct-bp__dot" style="background:{{ $typeAccent[$n->type] ?? $typeAccent['info'] }}"></span>
                            <span class="acct-bp__row-text">
                                <span class="acct-bp__title">{{ \Illuminate\Support\Str::limit($n->title, 48) }}</span>
                                @if($n->body)
                                    <span class="acct-bp__meta">{{ \Illuminate\Support\Str::limit(strip_tags($n->body), 56) }}</span>
                                @endif
                            </span>
                        </a>
                    </li>
                    @endforeach
                </ul>
            @endif

            @if(count($platformItems) > 0)
                <div class="acct-bp__section-title">اعلان سایت</div>
                <ul class="acct-bp__list">
                    @foreach($platformItems as $n)
                        @php $fresh = \App\Helpers\NotificationUi::isFresh($n['created_at'] ?? null); @endphp
                        <li>
                            <div class="acct-bp__row {{ $fresh ? 'is-new' : '' }}"
                                 style="border-inline-start:3px solid {{ $typeAccent[$n['type']] ?? $typeAccent['info'] }};">
                                @if($fresh)
                                    <span class="acct-bp__new-pill">جدید</span>
                                @endif
                                <span class="acct-bp__row-text">
                                    <span class="acct-bp__title">{{ \Illuminate\Support\Str::limit($n['title'], 52) }}</span>
                                    @if(!empty($n['body']))
                                        <span class="acct-bp__meta">{{ \Illuminate\Support\Str::limit(strip_tags($n['body']), 64) }}</span>
                                    @endif
                                    @if(!empty($n['link']))
                                        <a href="{{ $n['link'] }}" target="_blank" rel="noopener" class="acct-bp__cta">{{ $n['link_text'] ?: 'مشاهده' }}</a>
                                    @endif
                                </span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        @endif
    </div>
</div>

<script>
(function(){
    var el        = document.getElementById('account-notification-bell');
    var panel     = document.getElementById('acct-bell-panel');
    var firstHint = document.getElementById('bell-first-hint');
    if (!el || !panel) return;

    var stored = null;
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

    /* Position panel relative to bell location on screen */
    function positionPanel() {
        var r  = el.getBoundingClientRect();
        var wh = window.innerHeight;
        var ww = window.innerWidth;
        var bellSize = 56;

        /* Vertical: open upward if bell is in bottom half */
        if (r.top + bellSize / 2 > wh / 2) {
            panel.style.bottom = (bellSize + 8) + 'px';
            panel.style.top    = 'auto';
        } else {
            panel.style.top    = (bellSize + 8) + 'px';
            panel.style.bottom = 'auto';
        }

        /* Horizontal: align to the nearer edge */
        if (r.left + bellSize / 2 > ww / 2) {
            panel.style.right = '0';
            panel.style.left  = 'auto';
        } else {
            panel.style.left  = '0';
            panel.style.right = 'auto';
        }
    }

    /* Toggle panel open/close */
    function togglePanel() {
        var isHidden = panel.hasAttribute('hidden');
        if (isHidden) {
            positionPanel();
            panel.removeAttribute('hidden');
        } else {
            panel.setAttribute('hidden', '');
        }
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

    function onEnd() {
        if (!dragging) return;
        dragging = false;
        el.classList.remove('dragging');
        document.body.style.userSelect = '';
        if (!moved) {
            togglePanel();
            return;
        }
        /* After drag: close panel and save position */
        panel.setAttribute('hidden', '');
        var r = el.getBoundingClientRect();
        try { localStorage.setItem('notif_bell_pos', JSON.stringify({ x: r.left, y: r.top })); } catch(e){}
    }

    /* Mouse */
    el.addEventListener('mousedown', function(e){ e.preventDefault(); onStart(e.clientX, e.clientY); });
    document.addEventListener('mousemove', function(e){ onMove(e.clientX, e.clientY); });
    document.addEventListener('mouseup',   function(){ onEnd(); });

    /* Touch */
    el.addEventListener('touchstart', function(e){ var t=e.touches[0]; onStart(t.clientX,t.clientY); }, {passive:true});
    document.addEventListener('touchmove',  function(e){ if(!dragging)return; e.preventDefault(); var t=e.touches[0]; onMove(t.clientX,t.clientY); }, {passive:false});
    document.addEventListener('touchend',   function(){ onEnd(); });

    /* Close on outside click */
    document.addEventListener('click', function(e){
        if (!panel.hasAttribute('hidden') && !el.contains(e.target)) {
            panel.setAttribute('hidden', '');
        }
    });
    panel.addEventListener('click', function(e){ e.stopPropagation(); });
})();
</script>
@endif


