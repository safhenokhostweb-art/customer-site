@php
    /**
     * Phase 5 / Wave 4 — P2-6
     * Banner برای خطای موقت ارتباط با پلتفرم (network/5xx/timeout).
     * زمانی نمایش داده می‌شود که آخرین تلاش revalidate در 30 دقیقهٔ گذشته به دلیل
     * خطای موقت skip شده باشد. ست شدن این banner لایسنس را invalid نمی‌کند.
     * در validation موفق بعدی به‌طور خودکار پاک می‌شود.
     */
    $license = \App\Models\License::current();
    $transientError = null;
    if ($license && $license->id) {
        try {
            $transientError = \Illuminate\Support\Facades\Cache::get(
                \App\Services\LicenseActivationService::transientErrorCacheKey($license->id)
            );
        } catch (\Throwable $e) {
            $transientError = null;
        }
    }
@endphp

@if(!empty($transientError) && is_array($transientError) && $license && !$license->isValid())
    <div class="admin-flash admin-flash--warning" role="status" aria-live="polite">
        <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <span>
            <strong>هشدار موقت لایسنس:</strong>
            آخرین تلاش هم‌گام‌سازی با پلتفرم ناموفق بود
            @if(!empty($transientError['http_status']))
                (HTTP {{ (int) $transientError['http_status'] }})
            @endif
            — لایسنس فعلی شما همچنان معتبر است و دسترسی به ماژول‌ها قطع نمی‌شود.
            ممکن است موقتاً ارتباط با پلتفرم برقرار نباشد. اگر این پیام مدت طولانی باقی ماند، اتصال اینترنت سرور و آدرس <code>PLATFORM_URL</code> را بررسی کنید.
            <a href="{{ route('admin.license.refresh-config.get') }}" class="admin-link" style="text-decoration: underline; margin-right: 0.5rem;">تلاش مجدد</a>
        </span>
    </div>
@endif
