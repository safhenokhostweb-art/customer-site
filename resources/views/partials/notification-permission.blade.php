{{--
 | Notification Permission Banner — reusable across all panels.
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
     role="dialog" aria-label="فعال‌سازی اعلان‌های مرورگر">
    <style>
    @keyframes npbSlide{from{opacity:0;transform:translateX(-50%) translateY(1.5rem)}to{opacity:1;transform:translateX(-50%) translateY(0)}}
    .npb-btn-primary{padding:.45rem 1.1rem;background:linear-gradient(135deg,#6366f1,#8b5cf6);border:none;border-radius:.65rem;color:#fff;font-size:.8rem;font-weight:700;cursor:pointer;transition:opacity .15s}
    .npb-btn-primary:hover{opacity:.88}
    .npb-btn-dismiss{padding:.45rem .9rem;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.22);border-radius:.65rem;color:rgba(255,255,255,.85);font-size:.78rem;cursor:pointer;transition:opacity .15s}
    .npb-btn-dismiss:hover{opacity:.75}
    </style>
    <div style="display:flex;align-items:flex-start;gap:.875rem">
        <div style="width:2.5rem;height:2.5rem;border-radius:50%;background:rgba(99,102,241,.4);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px">
            <svg style="width:1.2rem;height:1.2rem" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
        </div>
        <div style="flex:1;min-width:0">
            <p style="font-weight:700;font-size:.9rem;margin:0 0 .3rem 0">اعلان‌های مرورگر را فعال کنید 🔔</p>
            <p style="font-size:.78rem;opacity:.82;line-height:1.6;margin:0 0 .75rem 0">
                برای دریافت فوری پاسخ پشتیبانی حتی زمانی که این صفحه در پیش‌زمینه نیست، اعلان‌ها را فعال کنید.
            </p>
            <div id="npb-steps-{{ $ctx }}" style="font-size:.73rem;opacity:.7;line-height:1.7;margin-bottom:.85rem;display:none;background:rgba(255,255,255,.08);padding:.6rem .8rem;border-radius:.6rem">
                <strong>Chrome / Edge:</strong> روی آیکون 🔒 (یا ℹ) کنار آدرس سایت کلیک کنید ← «اعلان‌ها» ← «مجاز»<br>
                <strong>Firefox:</strong> روی آیکون سپر 🛡 در نوار آدرس ← «مجوزهای سایت» ← «اعلان‌ها مجاز»
            </div>
            <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap">
                <button class="npb-btn-primary" onclick="window._npbRequest('{{ $ctx }}')">
                    🔔 فعال‌سازی اعلان‌ها
                </button>
                <button class="npb-btn-dismiss" onclick="window._npbDismiss('{{ $ctx }}')">
                    بعداً
                </button>
                <button onclick="document.getElementById('npb-steps-{{ $ctx }}').style.display=document.getElementById('npb-steps-{{ $ctx }}').style.display==='none'?'block':'none'"
                        style="background:none;border:none;color:rgba(255,255,255,.6);font-size:.73rem;cursor:pointer;text-decoration:underline;padding:0">
                    راهنمای دستی
                </button>
            </div>
        </div>
        <button onclick="window._npbDismiss('{{ $ctx }}')"
                style="background:rgba(255,255,255,.15);border:none;border-radius:50%;width:1.75rem;height:1.75rem;color:#fff;cursor:pointer;font-size:1rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;line-height:1">×</button>
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
                    new Notification('اعلان‌ها با موفقیت فعال شدند ✓', {
                        body: 'از این پس پاسخ‌های پشتیبانی را فوری دریافت می‌کنید.',
                        icon: '/favicon.ico', dir: 'rtl', tag: 'npb-confirm-' + ctx
                    });
                } catch(e) {}
                // ثبت Service Worker و اشتراک Push برای نوتیفیکیشن حتی با بسته بودن مرورگر
                window._npbSubscribePush();
            }
        });
    };

    window._npbSubscribePush = function() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
        navigator.serviceWorker.register('/sw.js').then(function(reg) {
            // دریافت VAPID public key از سرور
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

    // اگر مجوز قبلاً داده شده، SW را register می‌کنیم (بدون نمایش بنر)
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

    // نمایش بنر پس از اولین تعامل کاربر (کلیک، اسکرول یا تایپ)
    // این روش با سیاست مرورگر سازگار است
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
            // سعی برای درخواست خودکار — در برخی مرورگرها کار می‌کند
            // (در Chrome این تابع در داخل user gesture است)
            Notification.requestPermission().then(function(p) {
                if (p !== 'default') {
                    window._npbDismiss(CTX); // مجوز داده یا رد شد، بنر را ببند
                }
            }).catch(function(){});
        }
    }

    // اگر کاربر در عرض ۴ ثانیه تعاملی نداشت، بنر را نشان بده
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
