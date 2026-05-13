<?php

namespace App\Providers;

use App\Models\Page;
use App\Models\Product;
use App\Observers\ProductObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureShopWriteRateLimiters();

        Product::observe(ProductObserver::class);

        View::composer('themes.default.layout', function ($view) {
            $data = $view->getData();
            if (!isset($data['pages']) && \Illuminate\Support\Facades\Schema::hasTable('pages')) {
                $view->with('pages', Page::published()->menu()->get());
            }
            // Page-level header/footer override: pass current page when on page.show
            if (!array_key_exists('currentPage', $data)) {
                $currentPage = null;
                if (isset($data['page']) && $data['page'] instanceof Page) {
                    $currentPage = $data['page'];
                } elseif (request()->routeIs('page.show') && request()->route('slug')) {
                    $currentPage = Page::published()->where('slug', request()->route('slug'))->first();
                }
                $view->with('currentPage', $currentPage);
            }
        });
    }

    /**
     * Phase 5 / Wave 5.3 / P1-5: rate limiters for shop write endpoints.
     *
     * هدف: محافظت در برابر spam و brute-force سفارش/ریویو/استوک‌نوتیف بدون
     * شکستن تجربهٔ کاربران واقعی. کلید‌ها ترجیحاً `user_id` کاربر احراز
     * هویت‌شده است، در غیر این صورت ترکیب IP + session_id (تا session
     * بدون cookie هم زیر چتر بیاید).
     *
     * تجاوز ⇒ پاسخ ۴۲۹ استاندارد Laravel با header `Retry-After`.
     * Backward-compat: هیچ کاربر طبیعی به این حد‌ها برنخواهد خورد.
     */
    protected function configureShopWriteRateLimiters(): void
    {
        // cart actions: add/update/remove → تعامل سبک با session.
        RateLimiter::for('shop-cart', function (Request $request) {
            return Limit::perMinute(60)->by($this->shopRateKey($request));
        });

        // checkout: ساخت order واقعی + email + decrement stock ⇒ بسیار محدودتر.
        RateLimiter::for('shop-checkout', function (Request $request) {
            return Limit::perMinute(5)->by($this->shopRateKey($request));
        });

        // review per product: حداکثر ۳ نظر در ۱۰ دقیقه برای جلوگیری از flood روی یک محصول.
        RateLimiter::for('shop-review', function (Request $request) {
            $product = $request->route('slug') ?: $request->input('product_id', 'na');
            return Limit::perMinutes(10, 3)->by($this->shopRateKey($request) . '|p:' . $product);
        });

        // stock notify: حداکثر ۵ درخواست در ساعت برای هر (کاربر|product).
        RateLimiter::for('shop-stock-notify', function (Request $request) {
            $product = $request->input('product_id', 'na');
            return Limit::perHour(5)->by($this->shopRateKey($request) . '|p:' . $product);
        });
    }

    /**
     * کلید پایه برای rate limiter shop:
     * - اگر کاربر authenticated است ⇒ `u:{id}` (یکسان بین مرورگرها/تب‌ها).
     * - در غیر این صورت ⇒ `g:{ip}:{session_id}` (anonymous با fallback روی session).
     *
     * گارد: اگر session هنوز شروع نشده باشد (یا `getId()` رشتهٔ خالی برگرداند)،
     * بخش session با ثابت `no-sess` پر می‌شود تا key هیچ‌وقت با `:` خالی پایان
     * نگیرد و چند کاربر anonymous مختلف در یک bucket نیفتند.
     */
    protected function shopRateKey(Request $request): string
    {
        $userId = optional($request->user())->id;
        if ($userId) {
            return 'u:' . $userId;
        }
        $sessionId = 'no-sess';
        if ($request->hasSession()) {
            $sid = (string) $request->session()->getId();
            if ($sid !== '') {
                $sessionId = substr($sid, 0, 32);
            }
        }
        $ip = (string) ($request->ip() ?? '');
        if ($ip === '') {
            $ip = '0.0.0.0';
        }
        return 'g:' . $ip . ':' . $sessionId;
    }
}
