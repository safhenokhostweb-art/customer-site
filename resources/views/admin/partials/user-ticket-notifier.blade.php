@php
$_utLastId = \SaaSPlatform\Modules\Tickets\Models\Ticket::withoutTenantIsolation()
    ->whereNull('site_id')
    ->max('id') ?? 0;
@endphp
<div id="ut-notifier"
     style="display:none;position:fixed;top:0;right:0;left:0;z-index:9100;padding:0.7rem 1.25rem;background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);color:#fff;box-shadow:0 4px 24px rgba(79,70,229,0.45);animation:utSlide .35s ease">
    <div style="max-width:1280px;margin:0 auto;display:flex;align-items:center;gap:0.875rem;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:0.5rem;flex-shrink:0">
            <svg style="width:1.125rem;height:1.125rem" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span id="ut-notifier-text" style="font-size:0.85rem;font-weight:700"></span>
        </div>
        <div id="ut-notifier-links" style="display:flex;gap:0.4rem;flex-wrap:wrap;flex:1"></div>
        <a id="ut-notifier-all" href="{{ route('admin.user-tickets.index') }}"
           style="font-size:0.75rem;font-weight:600;color:rgba(255,255,255,0.85);white-space:nowrap;text-decoration:underline">
            مشاهده همه
        </a>
        <button onclick="document.getElementById('ut-notifier').style.display='none'"
                style="display:flex;align-items:center;justify-content:center;width:1.625rem;height:1.625rem;background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.3);border-radius:50%;color:#fff;font-size:1rem;line-height:1;cursor:pointer;padding:0;flex-shrink:0"
                aria-label="بستن">×</button>
    </div>
</div>
<style>
@keyframes utSlide{from{transform:translateY(-100%);opacity:0}to{transform:translateY(0);opacity:1}}
</style>
<script>
(function(){
    var lastId = {{ (int) $_utLastId }};
    var banner = document.getElementById('ut-notifier');
    var bannerText = document.getElementById('ut-notifier-text');
    var bannerLinks = document.getElementById('ut-notifier-links');
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');

    function check(){
        fetch('/admin/user-tickets/check-new?last_id=' + lastId, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfMeta ? csrfMeta.content : ''
            }
        })
        .then(function(r){ return r.ok ? r.json() : null; })
        .then(function(data){
            if (!data || data.new_count === 0) return;
            lastId = data.last_id;
            if (window.CS) window.CS.play('new_ticket');
            bannerText.textContent = data.new_count + ' تیکت جدید دریافت شد';
            while (bannerLinks.firstChild) bannerLinks.removeChild(bannerLinks.firstChild);
            data.tickets.slice(0, 4).forEach(function(t){
                var a = document.createElement('a');
                a.href = t.url;
                a.style.cssText = 'display:inline-flex;align-items:center;padding:0.2rem 0.65rem;background:rgba(255,255,255,0.92);color:#4338ca;border-radius:0.375rem;font-size:0.7rem;font-weight:700;text-decoration:none;white-space:nowrap;gap:0.25rem';
                var num = document.createElement('span');
                num.textContent = t.ticket_number;
                var name = document.createElement('span');
                name.style.cssText = 'color:#6b7280;font-weight:400;max-width:8rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap';
                name.textContent = t.customer_name ? ' — ' + t.customer_name : '';
                a.appendChild(num);
                a.appendChild(name);
                bannerLinks.appendChild(a);
            });
            banner.style.display = 'block';
            banner.style.animation = 'none';
            void banner.offsetWidth;
            banner.style.animation = 'utSlide .35s ease';
            // عنوان پنجره
            if (!document.hasFocus()) {
                var orig = document.title;
                document.title = '🔔 ' + data.new_count + ' تیکت جدید | ' + orig;
                window.addEventListener('focus', function(){ document.title = orig; }, {once:true});
            }
            // مرورگر نوتیفیکیشن
            if ('Notification' in window && Notification.permission === 'granted') {
                var first = data.tickets[0];
                try { new Notification(data.new_count + ' تیکت جدید', {
                    body: first ? (first.ticket_number + (first.customer_name ? ' — ' + first.customer_name : '')) : '',
                    icon: '/favicon.ico', dir: 'rtl', tag: 'ut-new'
                }); } catch(e) {}
            }
        })
        .catch(function(){});
    }

    setTimeout(check, 9000);
    setInterval(check, 22000);
})();
</script>
