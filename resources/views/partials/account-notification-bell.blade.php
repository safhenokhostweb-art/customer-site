@php
    $position = \App\Models\Setting::get('notification_bell_position', 'bottom-right');
    $unreadCount = auth()->user()->notifications()->unread()->count();
    $positions = [
        'bottom-right' => 'bottom:1.5rem;right:1.5rem;',
        'bottom-left'  => 'bottom:1.5rem;left:1.5rem;',
        'top-right'    => 'top:1.5rem;right:1.5rem;',
        'top-left'     => 'top:1.5rem;left:1.5rem;',
    ];
    $style = $positions[$position] ?? $positions['bottom-right'];
@endphp
@if(\App\Helpers\SiteHelper::moduleEnabled('notifications'))
<style>
@keyframes account-bell-shake {
    0%,100%{transform:rotate(0)}
    10%,30%{transform:rotate(-12deg)}
    20%,40%{transform:rotate(12deg)}
    50%{transform:rotate(0)}
}
.account-notification-bell-link.has-unread { animation: account-bell-shake 2s ease-in-out infinite; }
.account-notification-bell-link.has-unread:hover { animation: none; }
</style>
<div id="account-notification-bell" style="position:fixed;{{ $style }}z-index:99999;">
    <a href="{{ route('account.notifications.index') }}" aria-label="اعلان‌ها" class="account-notification-bell-link {{ $unreadCount > 0 ? 'has-unread' : '' }}" style="
        position:relative;display:flex;align-items:center;justify-content:center;
        width:3rem;height:3rem;border-radius:50%;
        background:#4f46e5;color:#fff;text-decoration:none;
        box-shadow:0 4px 12px rgba(79,70,229,0.4);
        transition:box-shadow 0.2s;
    " onmouseover="this.style.boxShadow='0 6px 16px rgba(79,70,229,0.5)'" onmouseout="this.style.boxShadow='0 4px 12px rgba(79,70,229,0.4)'">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
        </svg>
        @if($unreadCount > 0)
        <span style="
            position:absolute;top:-4px;right:-4px;min-width:1.25rem;height:1.25rem;border-radius:9999px;
            background:#ef4444;color:#fff;font-size:0.7rem;font-weight:700;
            display:flex;align-items:center;justify-content:center;padding:0 4px;
        ">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
        @endif
    </a>
</div>
@endif
