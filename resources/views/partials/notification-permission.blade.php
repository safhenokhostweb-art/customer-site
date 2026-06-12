{{--
 | Notification Permission Banner â€” reusable across all panels.
 | Include with: @include('partials.notification-permission', ['context' => 'customer|admin|platform'])
 | Shows only when permission is 'default' and user hasn't dismissed.
 | Auto-hides permanently once permission is granted or denied.
--}}
@php $ctx = $context ?? 'default'; @endphp

<div id="notif-perm-banner-{{ $ctx }}"
     style="display:none;position:fixed;bottom:1.25rem;left:50%;transform:translateX(-50%);width:calc(100% - 2rem);max-width:540px;z-index:9500;
            background:linear-gradient(135deg,#1e1b4b 0%,#312e81 100%);color:#fff;
            border-radius:1.125rem;box-shadow:0 12px 40px rgba(0,0,0,.4);
            padding:1.125rem 1.25rem;animation:npbSlide .35s cubic-bezier(.16,1,.3,1)"
     role="dialog" aria-label="ظپط¹ط§ظ„â€Œط³ط§ط²غŒ ط§ط¹ظ„ط§ظ†â€Œظ‡ط§غŒ ظ…ط±ظˆط±ع¯ط±">
    <style>
    @keyframes npbSlide{from{opacity:0;transform:translateX(-50%) translateY(1.5rem)}to{opacity:1;transform:translateX(-50%) translateY(0)}}
    .npb-btn-primary{padding:.45rem 1.1rem;background:linear-gradient(135deg,#6366f1,#8b5cf6);border:none;border-radius:.65rem;color:#fff;font-size:.8rem;font-weight:700;cursor:pointer;transition:opacity .15s}
    .npb-btn-primary:hover{opacity:.88}
    .npb-btn-dismiss{padding:.45rem .9rem;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.22);border-radius:.65rem;color:rgba(255,255,255,.85);font-size:.78rem;cursor:pointer;transition:opacity .15s}
    .npb-btn-dismiss:hover{opacity:.75}
    </style>
    <div style="display:flex;align-items:flex-start;gap:.875rem">
        <div style="width:2.5rem;height:2.5rem;border-radius:50%;background:rgba(99,102,241,.4);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px">
            <x-core-ui::icon name="bell" style="width:1.2rem;height:1.2rem" style="width:2px;height:px" />
        </div>
        <div style="flex:1;min-width:0">
            <p style="font-weight:700;font-size:.9rem;margin:0 0 .3rem 0">ط§ط¹ظ„ط§ظ†â€Œظ‡ط§غŒ ظ…ط±ظˆط±ع¯ط± ط±ط§ ظپط¹ط§ظ„ ع©ظ†غŒط¯ ًں””</p>
            <p style="font-size:.78rem;opacity:.82;line-height:1.6;margin:0 0 .75rem 0">
                ط¨ط±ط§غŒ ط¯ط±غŒط§ظپطھ ظپظˆط±غŒ ظ¾ط§ط³ط® ظ¾ط´طھغŒط¨ط§ظ†غŒ ط­طھغŒ ط²ظ…ط§ظ†غŒ ع©ظ‡ ط§غŒظ† طµظپط­ظ‡ ط¯ط± ظ¾غŒط´â€Œط²ظ…غŒظ†ظ‡ ظ†غŒط³طھطŒ ط§ط¹ظ„ط§ظ†â€Œظ‡ط§ ط±ط§ ظپط¹ط§ظ„ ع©ظ†غŒط¯.
            </p>
            <div id="npb-steps-{{ $ctx }}" style="font-size:.73rem;opacity:.7;line-height:1.7;margin-bottom:.85rem;display:none;background:rgba(255,255,255,.08);padding:.6rem .8rem;border-radius:.6rem">
                <strong>Chrome / Edge:</strong> ط±ظˆغŒ ط¢غŒع©ظˆظ† ًں”’ (غŒط§ â„¹) ع©ظ†ط§ط± ط¢ط¯ط±ط³ ط³ط§غŒطھ ع©ظ„غŒع© ع©ظ†غŒط¯ â†گ آ«ط§ط¹ظ„ط§ظ†â€Œظ‡ط§آ» â†گ آ«ظ…ط¬ط§ط²آ»<br>
                <strong>Firefox:</strong> ط±ظˆغŒ ط¢غŒع©ظˆظ† ط³ظ¾ط± ًں›، ط¯ط± ظ†ظˆط§ط± ط¢ط¯ط±ط³ â†گ آ«ظ…ط¬ظˆط²ظ‡ط§غŒ ط³ط§غŒطھآ» â†گ آ«ط§ط¹ظ„ط§ظ†â€Œظ‡ط§ ظ…ط¬ط§ط²آ»
            </div>
            <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap">
                <button class="npb-btn-primary" onclick="window._npbRequest('{{ $ctx }}')">
                    ًں”” ظپط¹ط§ظ„â€Œط³ط§ط²غŒ ط§ط¹ظ„ط§ظ†â€Œظ‡ط§
                </button>
                <button class="npb-btn-dismiss" onclick="window._npbDismiss('{{ $ctx }}')">
                    ط¨ط¹ط¯ط§ظ‹
                </button>
                <button onclick="document.getElementById('npb-steps-{{ $ctx }}').style.display=document.getElementById('npb-steps-{{ $ctx }}').style.display==='none'?'block':'none'"
                        style="background:none;border:none;color:rgba(255,255,255,.6);font-size:.73rem;cursor:pointer;text-decoration:underline;padding:0">
                    ط±ط§ظ‡ظ†ظ…ط§غŒ ط¯ط³طھغŒ
                </button>
            </div>
        </div>
        <button onclick="window._npbDismiss('{{ $ctx }}')"
                style="background:rgba(255,255,255,.15);border:none;border-radius:50%;width:1.75rem;height:1.75rem;color:#fff;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;line-height:1">أ—</button>
    </div>
</div>

<script>
(function(){
    var CTX = '{{ $ctx }}';
    var STORAGE_KEY = 'npb_dismissed_' + CTX;

    function getBanner() { return document.getElementById('notif-perm-banner-' + CTX); }

    window._npbRequest = function(ctx) {
        if (ctx !== CTX) return;
        if (!('Notification' in window)) { window._npbDismiss(ctx); return; }
        Notification.requestPermission().then(function(p) {
            window._npbDismiss(ctx);
            if (p === 'granted') {
                try {
                    new Notification('ط§ط¹ظ„ط§ظ†â€Œظ‡ط§ ط¨ط§ ظ…ظˆظپظ‚غŒطھ ظپط¹ط§ظ„ ط´ط¯ظ†ط¯ âœ“', {
                        body: 'ط§ط² ط§غŒظ† ظ¾ط³ ظ¾ط§ط³ط®â€Œظ‡ط§غŒ ظ¾ط´طھغŒط¨ط§ظ†غŒ ط±ط§ ظپظˆط±غŒ ط¯ط±غŒط§ظپطھ ظ…غŒâ€Œع©ظ†غŒط¯.',
                        icon: '/favicon.ico', dir: 'rtl', tag: 'npb-confirm-' + ctx
                    });
                } catch(e) {}
                // ط«ط¨طھ Service Worker ظˆ ط§ط´طھط±ط§ع© Push ط¨ط±ط§غŒ ظ†ظˆطھغŒظپغŒع©غŒط´ظ† ط­طھغŒ ط¨ط§ ط¨ط³طھظ‡ ط¨ظˆط¯ظ† ظ…ط±ظˆط±ع¯ط±
                window._npbSubscribePush();
            }
        });
    };

    window._npbSubscribePush = function() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
        navigator.serviceWorker.register('/sw.js').then(function(reg) {
            // ط¯ط±غŒط§ظپطھ VAPID public key ط§ط² ط³ط±ظˆط±
            fetch('/api/push/vapid-key')
                .then(function(r){ return r.json(); })
                .then(function(data) {
                    if (!data.public_key) return;
                    var key = _npbUrlBase64ToUint8Array(data.public_key);
                    return reg.pushManager.subscribe({
                        userVisibleOnly:      true,
                        applicationServerKey: key,
                    });
                })
                .then(function(sub) {
                    if (!sub) return;
                    var j   = sub.toJSON();
                    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
                    fetch('/api/push/subscribe', {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body:    JSON.stringify({
                            endpoint:         j.endpoint,
                            public_key:       j.keys?.p256dh,
                            auth_token:       j.keys?.auth,
                            content_encoding: 'aesgcm',
                            panel_context:    CTX,
                        }),
                    });
                })
                .catch(function(e){ console.warn('Push subscribe failed:', e); });
        }).catch(function(e){ console.warn('SW register failed:', e); });
    };

    function _npbUrlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - base64String.length % 4) % 4);
        var base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var raw     = window.atob(base64);
        var arr     = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i);
        return arr;
    }

    // ط§ع¯ط± ظ…ط¬ظˆط² ظ‚ط¨ظ„ط§ظ‹ ط¯ط§ط¯ظ‡ ط´ط¯ظ‡طŒ SW ط±ط§ register ظ…غŒâ€Œع©ظ†غŒظ… (ط¨ط¯ظˆظ† ظ†ظ…ط§غŒط´ ط¨ظ†ط±)
    if (Notification.permission === 'granted') {
        window._npbSubscribePush();
    }

    window._npbDismiss = function(ctx) {
        if (ctx !== CTX) return;
        localStorage.setItem(STORAGE_KEY, '1');
        var b = getBanner();
        if (b) {
            b.style.animation = 'none';
            b.style.opacity = '0';
            b.style.transform = 'translateX(-50%) translateY(1rem)';
            b.style.transition = 'opacity .25s,transform .25s';
            setTimeout(function(){ b.style.display = 'none'; }, 260);
        }
    };

    function shouldShow() {
        if (!('Notification' in window)) return false;
        if (Notification.permission !== 'default') return false;
        if (localStorage.getItem(STORAGE_KEY) === '1') return false;
        return true;
    }

    if (!shouldShow()) return;

    // ظ†ظ…ط§غŒط´ ط¨ظ†ط± ظ¾ط³ ط§ط² ط§ظˆظ„غŒظ† طھط¹ط§ظ…ظ„ ع©ط§ط±ط¨ط± (ع©ظ„غŒع©طŒ ط§ط³ع©ط±ظˆظ„ غŒط§ طھط§غŒظ¾)
    // ط§غŒظ† ط±ظˆط´ ط¨ط§ ط³غŒط§ط³طھ ظ…ط±ظˆط±ع¯ط± ط³ط§ط²ع¯ط§ط± ط§ط³طھ
    var _shown = false;
    function showBanner() {
        if (_shown) return;
        _shown = true;
        ['click', 'keydown', 'scroll', 'touchstart'].forEach(function(e) {
            document.removeEventListener(e, showBanner);
        });
        if (!shouldShow()) return; // double-check after interaction
        var b = getBanner();
        if (b) {
            b.style.display = 'block';
            // ط³ط¹غŒ ط¨ط±ط§غŒ ط¯ط±ط®ظˆط§ط³طھ ط®ظˆط¯ع©ط§ط± â€” ط¯ط± ط¨ط±ط®غŒ ظ…ط±ظˆط±ع¯ط±ظ‡ط§ ع©ط§ط± ظ…غŒâ€Œع©ظ†ط¯
            // (ط¯ط± Chrome ط§غŒظ† طھط§ط¨ط¹ ط¯ط± ط¯ط§ط®ظ„ user gesture ط§ط³طھ)
            Notification.requestPermission().then(function(p) {
                if (p !== 'default') {
                    window._npbDismiss(CTX); // ظ…ط¬ظˆط² ط¯ط§ط¯ظ‡ غŒط§ ط±ط¯ ط´ط¯طŒ ط¨ظ†ط± ط±ط§ ط¨ط¨ظ†ط¯
                }
            }).catch(function(){});
        }
    }

    // ط§ع¯ط± ع©ط§ط±ط¨ط± ط¯ط± ط¹ط±ط¶ غ´ ط«ط§ظ†غŒظ‡ طھط¹ط§ظ…ظ„غŒ ظ†ط¯ط§ط´طھطŒ ط¨ظ†ط± ط±ط§ ظ†ط´ط§ظ† ط¨ط¯ظ‡
    var autoTimer = setTimeout(function() {
        if (!_shown && shouldShow()) {
            _shown = true;
            var b = getBanner();
            if (b) b.style.display = 'block';
        }
    }, 4000);

    ['click', 'keydown', 'scroll', 'touchstart'].forEach(function(e) {
        document.addEventListener(e, function handler() {
            clearTimeout(autoTimer);
            showBanner();
            document.removeEventListener(e, handler);
        }, { once: true, passive: true });
    });
})();
</script>


