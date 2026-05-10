@php
    $inboxUnread = \App\Models\UserNotification::where('user_id', auth()->id())->unread()->count();
    $inboxRecent = \App\Models\UserNotification::where('user_id', auth()->id())
        ->latest()
        ->limit(5)
        ->get();
    $platformPublic = collect();
    if (\App\Helpers\SiteHelper::moduleEnabled('notifications') && class_exists(\App\Services\PlatformUxService::class)) {
        $platformPublic = collect(app(\App\Services\PlatformUxService::class)->getPlatformNotifications())
            ->filter(fn ($n) => ($n['audience'] ?? 'public') === 'public');
    }
    $platformItems = \App\Helpers\NotificationUi::sortToolbarItems(
        $platformPublic->map(fn ($n) => [
            'id' => isset($n['id']) ? 'p_' . $n['id'] : 'p_' . md5(($n['title'] ?? '') . ($n['body'] ?? '')),
            'title' => $n['title'] ?? '',
            'body' => $n['body'] ?? null,
            'type' => $n['type'] ?? 'info',
            'link' => $n['link'] ?? null,
            'link_text' => $n['link_text'] ?? null,
            'priority' => $n['priority'] ?? 'normal',
            'created_at' => $n['created_at'] ?? null,
        ])->values()->all()
    );
    $typeAccent = ['info' => '#2563eb', 'success' => '#16a34a', 'warning' => '#d97706', 'danger' => '#dc2626'];
@endphp
<div class="acct-notif-toolbar relative" id="acct-notif-toolbar">
    <button type="button"
            class="acct-notif-toolbar__btn"
            id="acct-notif-toolbar-btn"
            aria-expanded="false"
            aria-controls="acct-notif-toolbar-panel"
            aria-label="اعلان‌ها">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        @if($inboxUnread > 0)
            <span class="acct-notif-toolbar__badge">{{ $inboxUnread > 99 ? '۹۹+' : $inboxUnread }}</span>
        @endif
    </button>
    <div class="acct-notif-toolbar__panel" id="acct-notif-toolbar-panel" hidden>
        <div class="acct-notif-toolbar__head">
            <span>اعلان‌ها</span>
            <a href="{{ route('account.notifications.index') }}" class="acct-notif-toolbar__link-all">همه پیام‌ها</a>
        </div>
        @if($inboxRecent->isEmpty() && count($platformItems) === 0)
            <div class="acct-notif-toolbar__empty">موردی برای نمایش نیست.</div>
        @else
            @if($inboxRecent->isNotEmpty())
                <div class="acct-notif-toolbar__section-title">صندوق ورودی</div>
                <ul class="acct-notif-toolbar__list">
                    @foreach($inboxRecent as $n)
                        <li>
                            <a href="{{ route('account.notifications.index') }}" class="acct-notif-toolbar__row {{ $n->read_at ? '' : 'is-new' }}">
                                <span class="acct-notif-toolbar__dot" style="background:{{ $typeAccent[$n->type] ?? $typeAccent['info'] }}"></span>
                                <span class="acct-notif-toolbar__row-text">
                                    <span class="acct-notif-toolbar__title">{{ \Illuminate\Support\Str::limit($n->title, 48) }}</span>
                                    @if($n->body)
                                        <span class="acct-notif-toolbar__meta">{{ \Illuminate\Support\Str::limit(strip_tags($n->body), 56) }}</span>
                                    @endif
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
            @if(count($platformItems) > 0)
                <div class="acct-notif-toolbar__section-title">اعلان سایت</div>
                <ul class="acct-notif-toolbar__list">
                    @foreach($platformItems as $n)
                        @php $fresh = \App\Helpers\NotificationUi::isFresh($n['created_at'] ?? null); @endphp
                        <li>
                            <div class="acct-notif-toolbar__row {{ $fresh ? 'is-new' : '' }}" style="border-inline-start: 3px solid {{ $typeAccent[$n['type']] ?? $typeAccent['info'] }};">
                                @if($fresh)
                                    <span class="acct-notif-toolbar__new-pill">جدید</span>
                                @endif
                                <span class="acct-notif-toolbar__row-text">
                                    <span class="acct-notif-toolbar__title">{{ \Illuminate\Support\Str::limit($n['title'], 52) }}</span>
                                    @if(!empty($n['body']))
                                        <span class="acct-notif-toolbar__meta">{{ \Illuminate\Support\Str::limit(strip_tags($n['body']), 64) }}</span>
                                    @endif
                                    @if(!empty($n['link']))
                                        <a href="{{ $n['link'] }}" target="_blank" rel="noopener" class="acct-notif-toolbar__cta">{{ $n['link_text'] ?: 'مشاهده' }}</a>
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
<style>
    .acct-notif-toolbar { position: relative; }
    .acct-notif-toolbar__btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.65rem;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        cursor: pointer;
        transition: background .15s, border-color .15s, color .15s;
    }
    .acct-notif-toolbar__btn:hover { background: #eef2ff; border-color: #c7d2fe; color: #4338ca; }
    .acct-notif-toolbar__badge {
        position: absolute;
        top: -0.35rem;
        inset-inline-start: -0.35rem;
        min-width: 1.1rem;
        height: 1.1rem;
        padding: 0 4px;
        border-radius: 9999px;
        background: #dc2626;
        color: #fff;
        font-size: 0.65rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }
    .acct-notif-toolbar__panel {
        position: absolute;
        top: calc(100% + 6px);
        inset-inline-end: 0;
        width: min(22rem, calc(100vw - 2rem));
        max-height: min(70vh, 24rem);
        overflow-y: auto;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
        z-index: 80;
        font-family: inherit;
    }
    .acct-notif-toolbar__panel[hidden] { display: none !important; }
    .acct-notif-toolbar__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.65rem 0.85rem;
        border-bottom: 1px solid #f1f5f9;
        font-weight: 700;
        font-size: 0.875rem;
        color: #0f172a;
    }
    .acct-notif-toolbar__link-all { font-size: 0.75rem; font-weight: 600; color: #4f46e5; text-decoration: none; }
    .acct-notif-toolbar__link-all:hover { text-decoration: underline; }
    .acct-notif-toolbar__empty { padding: 1.25rem; text-align: center; color: #64748b; font-size: 0.875rem; }
    .acct-notif-toolbar__section-title {
        padding: 0.5rem 0.85rem 0.25rem;
        font-size: 0.7rem;
        font-weight: 700;
        color: #94a3b8;
        letter-spacing: 0.04em;
    }
    .acct-notif-toolbar__list { list-style: none; margin: 0; padding: 0 0 0.5rem; }
    .acct-notif-toolbar__row {
        display: flex;
        gap: 0.5rem;
        align-items: flex-start;
        padding: 0.55rem 0.85rem;
        margin: 0 0.5rem 0.35rem;
        border-radius: 0.5rem;
        background: #fafafa;
        border: 1px solid #f1f5f9;
        text-decoration: none;
        color: inherit;
        position: relative;
    }
    .acct-notif-toolbar__row.is-new { background: #f5f3ff; border-color: #ddd6fe; }
    .acct-notif-toolbar__dot { width: 6px; height: 6px; border-radius: 50%; margin-top: 0.35rem; flex-shrink: 0; }
    .acct-notif-toolbar__row-text { min-width: 0; flex: 1; }
    .acct-notif-toolbar__title { display: block; font-size: 0.8125rem; font-weight: 600; color: #1e293b; line-height: 1.35; }
    .acct-notif-toolbar__meta { display: block; font-size: 0.75rem; color: #64748b; margin-top: 0.2rem; line-height: 1.4; }
    .acct-notif-toolbar__cta { display: inline-block; margin-top: 0.35rem; font-size: 0.72rem; font-weight: 600; color: #4f46e5; }
    .acct-notif-toolbar__new-pill {
        position: absolute;
        top: 0.35rem;
        inset-inline-end: 0.35rem;
        font-size: 0.62rem;
        font-weight: 800;
        color: #6d28d9;
        background: #ede9fe;
        padding: 0.1rem 0.35rem;
        border-radius: 0.25rem;
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var root = document.getElementById('acct-notif-toolbar');
    if (!root) return;
    var btn = document.getElementById('acct-notif-toolbar-btn');
    var panel = document.getElementById('acct-notif-toolbar-panel');
    if (!btn || !panel) return;
    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        var open = panel.hasAttribute('hidden');
        if (open) {
            panel.removeAttribute('hidden');
            btn.setAttribute('aria-expanded', 'true');
        } else {
            panel.setAttribute('hidden', '');
            btn.setAttribute('aria-expanded', 'false');
        }
    });
    document.addEventListener('click', function () {
        if (!panel.hasAttribute('hidden')) {
            panel.setAttribute('hidden', '');
            btn.setAttribute('aria-expanded', 'false');
        }
    });
    panel.addEventListener('click', function (e) { e.stopPropagation(); });
});
</script>
