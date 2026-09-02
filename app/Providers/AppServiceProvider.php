<?php

namespace App\Providers;

use App\Models\Page;
use App\Models\Product;
use App\Observers\ProductObserver;
use App\Repositories\SiteRepository;
use App\Services\CaptchaService;
use SaaSPlatform\CoreUtils\Contracts\Repositories\SiteRepositoryInterface;
use SaaSPlatform\CoreUtils\Contracts\Services\ActivityLoggerInterface;
use SaaSPlatform\CoreUtils\Contracts\Services\AuthUserResolverInterface;
use SaaSPlatform\CoreUtils\Contracts\Services\CaptchaServiceInterface;
use SaaSPlatform\CoreUtils\Contracts\Services\DateFormatterInterface;
use SaaSPlatform\CoreUtils\Contracts\Services\ModuleLicenseServiceInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use SaaSPlatform\CoreUtils\Contracts\TenantContextContract;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \SaaSPlatform\Modules\Forms\Contracts\FormServiceInterface::class,
            \SaaSPlatform\Modules\Forms\Services\FormService::class
        );

        $this->app->bind(
            CaptchaServiceInterface::class,
            CaptchaService::class
        );

        $this->app->bind(
            SiteRepositoryInterface::class,
            SiteRepository::class
        );

        // Phase 3E-E (correction) — ExtensionEntitlementProviderContract's
        // V1 bridge implementation. Deliberately `bind()`, not
        // `singleton()`, for the identical reason the resolver below is:
        // it transitively holds a TenantContext and must resolve the
        // current tenant fresh, never sharing a cached entitlement across
        // requests.
        $this->app->bind(
            \App\Services\ThemeCanvas\Dependency\ExtensionEntitlementProviderContract::class,
            \App\Services\ThemeCanvas\Dependency\ConfigExtensionEntitlementProvider::class
        );

        // Phase 3E-A/3D-F/3E-E — registered dependency-type adapters:
        // 'module' (via the already-existing, product-neutral
        // TenantContext::hasModule()) and 'extension' (Phase 3E-E, the
        // third-party Extension SDK's PRODUCT identity — vendor/package,
        // never a bare vendor — gated by BOTH InstalledExtensionProvider's
        // package-presence fact AND ExtensionEntitlementProviderContract's
        // independent tenant-entitlement fact; neither alone is sufficient).
        // Motion/Icons/etc. register their own adapters here later;
        // ThemeUpgradeReconciler and the reconciliation gate never need to
        // know which adapters exist.
        //
        // Deliberately `bind()`, not `singleton()`: this transitively holds
        // a TenantContext, which caches the resolved License for its own
        // lifetime — fine for the lifetime of one real HTTP request under
        // traditional PHP-FPM (a fresh process per request), but a singleton
        // here would let that cached license silently outlive a single
        // request under any persistent-worker deployment (e.g. Octane),
        // serving a stale entitlement to a later request. Constructing this
        // is cheap; there is no reason to share the instance.
        $this->app->bind(
            \App\Services\ThemeCanvas\Dependency\ThemeDependencyAvailabilityResolver::class,
            fn ($app) => new \App\Services\ThemeCanvas\Dependency\ThemeDependencyAvailabilityResolver([
                $app->make(\App\Services\ThemeCanvas\Dependency\ModuleDependencyResolver::class),
                $app->make(\App\Services\ThemeCanvas\Dependency\ExtensionDependencyResolver::class),
            ])
        );

        $this->app->bind(
            AuthUserResolverInterface::class,
            \App\Services\AuthUserResolverAdapter::class
        );

        $this->app->bind(
            ModuleLicenseServiceInterface::class,
            \App\Services\NullModuleLicenseAdapter::class
        );

        // Phase 18-B — generic SEO extension point default. Any module
        // that wants to contribute meta/JSON-LD rebinds this in its own
        // ServiceProvider; Core never references a specific module here.
        $this->app->bind(
            \SaaSPlatform\CoreUtils\Contracts\Services\SeoOverrideProviderInterface::class,
            \App\Services\Seo\NullSeoOverrideProvider::class
        );

        // Phase 18-C — Part 3: Core is the only side that knows its own
        // content models, so (unlike SeoOverrideProviderInterface) this is
        // bound directly to the real implementation, not a Null default —
        // any sitemap-producing module consumes it without ever
        // referencing Page/Post/Product itself.
        $this->app->bind(
            \SaaSPlatform\CoreUtils\Contracts\Services\SitemapUrlProviderInterface::class,
            \App\Services\Seo\CoreSitemapUrlProvider::class
        );

        $this->app->bind(
            DateFormatterInterface::class,
            \App\Services\JalaliDateFormatterAdapter::class
        );

        // PHASE UI-THEME.1 — Enterprise Theme Engine (tenant resolver)
        $this->app->singleton(\App\Services\ThemeEngine\TenantThemeResolver::class);

        // Membership Benefits Engine
        $this->app->singleton(\App\Services\MembershipBenefitService::class);
        $this->app->singleton(\App\Services\MembershipPointsService::class);
        $this->app->singleton(\App\Services\GroupSubscriptionService::class);

        // PHASE M3.2 — DOMAIN A: Tenant Support Runtime
        $this->app->singleton(\App\Services\TenantSupportRuntime\TenantChannelRegistry::class);
        $this->app->singleton(\App\Services\TenantSupportRuntime\TenantPresenceService::class);
        $this->app->singleton(\App\Services\TenantSupportRuntime\TenantAttachmentService::class);
        $this->app->singleton(\App\Services\TenantSupportRuntime\TenantIsolationValidator::class);
        $this->app->singleton(\App\Services\TenantSupportRuntime\TenantMessageService::class);
        $this->app->singleton(\App\Services\TenantSupportRuntime\TenantConversationService::class);
        $this->app->singleton(\App\Services\SupportModuleLicenseGate::class);
        $this->app->singleton(\App\Services\TicketConversationBridge::class);

        // Bind ActivityLoggerInterface
$this->app->bind(
    ActivityLoggerInterface::class,
    function () {
        return new class implements ActivityLoggerInterface {
            public function log(string $action, ?string $description = null, ?object $subject = null, array $properties = []): void
            {
                // No-op implementation for tenant site
            }

            public function getActivityLog(string $entity, ?int $entityId = null, int $limit = 50): array
            {
                return [];
            }
        };
    }
);
    }

    public function boot(): void
    {
        // ── PERSIAN LOCALIZATION GLOBAL STANDARD — Blade Directives ────────────
        Blade::directive('numFa', function ($expression) {
            return "<?php echo \\App\\Services\\PersianFormatter::num($expression); ?>";
        });
        Blade::directive('priceFa', function ($expression) {
            return "<?php echo \\App\\Services\\PersianFormatter::currency($expression); ?>";
        });
        Blade::directive('dateFa', function ($expression) {
            return "<?php echo \\App\\Services\\PersianFormatter::date($expression); ?>";
        });
        Blade::directive('dateFaLong', function ($expression) {
            return "<?php echo \\App\\Services\\PersianFormatter::dateLong($expression); ?>";
        });
        Blade::directive('dateFaShort', function ($expression) {
            return "<?php echo \\App\\Services\\PersianFormatter::dateShort($expression); ?>";
        });
        Blade::directive('datetimeFa', function ($expression) {
            return "<?php echo \\App\\Services\\PersianFormatter::datetime($expression); ?>";
        });
        Blade::directive('agoFa', function ($expression) {
            return "<?php echo \\App\\Services\\PersianFormatter::ago($expression); ?>";
        });
        Blade::directive('digitsFa', function ($expression) {
            return "<?php echo \\App\\Services\\PersianFormatter::digits($expression); ?>";
        });

        // Register local core-ui component path as a fallback for production
        // where saas-platform/core-utils package may not be installed.
        // On dev, the package's CoreUtilsServiceProvider registers first and its
        // path takes precedence. On production this is the only registration.
        Blade::anonymousComponentPath(resource_path('views/components/core-ui'), 'core-ui');

        $this->configureShopWriteRateLimiters();
        $this->configureFormsWriteRateLimiters();

        Product::observe(ProductObserver::class);
        \App\Models\User::observe(\App\Observers\UserObserver::class);
        \App\Models\Order::observe(\App\Observers\OrderObserver::class);
        if (\Illuminate\Support\Facades\Schema::hasTable('support_conversations')) {
            \App\Models\SupportConversation::observe(\App\Observers\SupportConversationObserver::class);
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('subscriptions')) {
            \App\Models\Subscription::observe(\App\Observers\SubscriptionObserver::class);
        }

        // Phase 18-B — registered on the wildcard theme pattern (not just
        // 'themes.default.layout') so a future second theme automatically
        // gets the same behavior without any further Core change, as long
        // as its layout also has a @stack('json-ld').
        View::composer('themes.*.layout', function ($view) {
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

            $this->pushExtraJsonLd($view);
        });
    }

    /**
     * Phase 18-B: pushes any module-contributed JSON-LD (via
     * SeoOverrideProviderInterface::extraJsonLd(), default Null Object —
     * see NullSeoOverrideProvider) onto the theme layout's EXISTING
     * @stack('json-ld') — no Blade file was edited to add this stack, it
     * was already there. Defensive: any failure here must never break
     * page rendering.
     */
    protected function pushExtraJsonLd($view): void
    {
        try {
            $extra = $this->app->make(\SaaSPlatform\CoreUtils\Contracts\Services\SeoOverrideProviderInterface::class)->extraJsonLd();
        } catch (\Throwable $e) {
            return;
        }

        if (empty($extra)) {
            return;
        }

        $factory = $view->getFactory();
        $factory->startPush('json-ld');
        foreach ($extra as $schema) {
            if (!empty($schema)) {
                echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
            }
        }
        $factory->stopPush();
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
     * Phase 2 / Wave 7.0b: rate limiter for public form submissions.
     *
     * The legacy `POST /forms/{slug}` (`forms.submit`) had no throttle at
     * all, so a single client could spam unlimited submissions per second.
     * The limit is 10/minute scoped to identity + slug:
     *  - Identity prefers `user_id` (authenticated submissions) and falls
     *    back to the client IP. Logged-in users get their own bucket even
     *    on shared NAT/VPN exits; anonymous traffic shares an IP bucket
     *    per slug, which is intentionally permissive enough for shared
     *    office/university networks but still blocks single-host flood.
     *  - Per-slug scoping means a malicious payload against one form
     *    does not consume the quota for unrelated forms on the same site.
     *
     * Hitting the limit returns a 429 with `Retry-After`; CaptchaService
     * already covers human-vs-bot challenges when the captcha module is
     * enabled, so this throttle is the second (not only) line of defence.
     */
    protected function configureFormsWriteRateLimiters(): void
    {
        RateLimiter::for('forms-submit', function (Request $request) {
            $slug = (string) ($request->route('slug') ?: 'na');
            $identity = optional($request->user())->id
                ? 'u:' . $request->user()->id
                : 'ip:' . ((string) ($request->ip() ?? '0.0.0.0'));

            return Limit::perMinute(10)->by($identity . '|f:' . $slug);
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
