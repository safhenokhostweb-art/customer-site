@php
use SaaSPlatform\Modules\Tickets\Models\Ticket as _ATTicket;
use SaaSPlatform\Modules\Tickets\Models\TicketReply as _ATReply;

$_atIds = _ATTicket::withoutTenantIsolation()
    ->where('customer_id', auth()->id())
    ->whereNull('site_id')
    ->pluck('id');

$_atLastReplyId = $_atIds->isNotEmpty()
    ? (_ATReply::whereIn('ticket_id', $_atIds)->max('id') ?? 0)
    : 0;
@endphp

@if($_atIds->isNotEmpty())
<div id="atn-banner"
     style="display:none;position:fixed;top:0;right:0;left:0;z-index:9100;padding:0.7rem 1.25rem;background:linear-gradient(135deg,#0ea5e9 0%,#6366f1 100%);color:#fff;box-shadow:0 4px 24px rgba(99,102,241,0.4);animation:atnSlide .35s ease">
    <div style="max-width:960px;margin:0 auto;display:flex;align-items:center;gap:0.875rem">
        <x-core-ui::icon name="message-circle" style="width:1.125rem;height:1.125rem;flex-shrink:0" style="width:2px;height:px" />
        <span id="atn-text" style="flex:1;font-size:0.85rem;font-weight:600;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></span>
        <a id="atn-link" href="#"
           style="padding:0.25rem 0.85rem;background:rgba(255,255,255,0.9);color:#4f46e5;border-radius:0.4rem;font-size:0.75rem;font-weight:700;text-decoration:none;white-space:nowrap;flex-shrink:0">
            ظ…ط´ط§ظ‡ط¯ظ‡ ظ¾ط§ط³ط®
        </a>
        <button onclick="document.getElementById('atn-banner').style.display='none'"
                style="display:flex;align-items:center;justify-content:center;width:1.625rem;height:1.625rem;background:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.3);border-radius:50%;color:#fff;font-size:1rem;cursor:pointer;padding:0;flex-shrink:0"
                aria-label="ط¨ط³طھظ†">أ—</button>
    </div>
</div>
<style>
@keyframes atnSlide{from{transform:translateY(-100%);opacity:0}to{transform:translateY(0);opacity:1}}
</style>
<script>
(function(){
    var lastId = {{ (int) $_atLastReplyId }};
    var banner = document.getElementById('atn-banner');
    var bannerText = document.getElementById('atn-text');
    var bannerLink = document.getElementById('atn-link');
    var dismissTimer = null;
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');

    function showBanner(text, url){
        bannerText.textContent = text;
        bannerLink.href = url;
        banner.style.display = 'block';
        banner.style.animation = 'none';
        void banner.offsetWidth;
        banner.style.animation = 'atnSlide .35s ease';
        if (dismissTimer) clearTimeout(dismissTimer);
        dismissTimer = setTimeout(function(){ banner.style.display = 'none'; }, 8000);
    }

    function check(){
        fetch('/account/tickets/check-activity?last_id=' + lastId, {
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
            if (window.CS) window.CS.play('notify');
            var first = data.replies[0];
            var text = 'ظ¾ط§ط³ط® ط¬ط¯غŒط¯ ط¯ط±غŒط§ظپطھ ط´ط¯: ' + (first.subject || 'طھغŒع©طھ ط´ظ…ط§');
            if (data.new_count > 1) text += ' (+' + (data.new_count - 1) + ' ظ…ظˆط±ط¯ ط¯غŒع¯ط±)';
            showBanner(text, first.url);
        })
        .catch(function(){});
    }

    setTimeout(check, 6000);
    setInterval(check, 16000);
})();
</script>
@endif


