<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

/**
 * Wave 5.3 / P1-5 — Shop write endpoints guards:
 *   • Phase 5.3A: 4 named rate limiters (shop-cart / shop-checkout /
 *     shop-review / shop-stock-notify).
 *   • Phase 5.3B: conditional captcha for guests on checkout, review
 *     and stock-notify when shop_captcha_enabled=1.
 *   • Phase 5.3C: admin toggle for shop_captcha_enabled (Setting key).
 *
 * Backward-compat contract:
 *   • Default shop_captcha_enabled=0 → no UX change, no captcha gate.
 *   • Authenticated users always bypass captcha.
 *   • Rate-limit defaults are well above honest-user behaviour.
 */
class Wave53ShopGuardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure a fresh limiter cache for each test (file driver in CI).
        RateLimiter::clear('shop-cart');
        RateLimiter::clear('shop-checkout');
        RateLimiter::clear('shop-review');
        RateLimiter::clear('shop-stock-notify');
        // Reset Setting cache between tests.
        Cache::forget('settings');
    }

    /** Seed a valid license with the shop module enabled for the host. */
    private function enableShop(string $host): License
    {
        $license = License::create([
            'license_key' => 'W53-SHOP-' . uniqid(),
            'domain' => $host,
            'expires_at' => now()->addYear(),
            'modules' => ['shop'],
            'platform_valid' => true,
        ]);
        return $license;
    }

    private function user(bool $admin = false): User
    {
        return User::create([
            'name' => 'W53 ' . ($admin ? 'Admin' : 'User'),
            'email' => 'w53_' . uniqid() . '@example.com',
            'mobile' => '0912' . random_int(1000000, 9999999),
            'password' => bcrypt('Secret123!'),
            'is_admin' => $admin,
            'is_active' => true,
        ]);
    }

    // =====================================================================
    // Phase 5.3A — rate limiters
    // =====================================================================

    public function test_rate_limiters_are_named_and_registered(): void
    {
        // The four limiters must resolve a Limit/array from the registry.
        $reg = app(\Illuminate\Cache\RateLimiter::class);
        foreach (['shop-cart', 'shop-checkout', 'shop-review', 'shop-stock-notify'] as $name) {
            $this->assertNotNull(
                $reg->limiter($name),
                "limiter '$name' must be registered in AppServiceProvider"
            );
        }
    }

    public function test_shop_cart_route_has_throttle_middleware_attached(): void
    {
        $route = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->first(fn ($r) => $r->getName() === 'shop.cart.add');
        $this->assertNotNull($route);
        $this->assertContains('throttle:shop-cart', $route->gatherMiddleware());
    }

    public function test_shop_checkout_route_has_throttle_middleware_attached(): void
    {
        $route = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->first(fn ($r) => $r->getName() === 'shop.checkout');
        $this->assertNotNull($route);
        $this->assertContains('throttle:shop-checkout', $route->gatherMiddleware());
    }

    public function test_shop_review_route_has_throttle_middleware_attached(): void
    {
        $route = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->first(fn ($r) => $r->getName() === 'shop.reviews.store');
        $this->assertNotNull($route);
        $this->assertContains('throttle:shop-review', $route->gatherMiddleware());
    }

    public function test_shop_stock_notify_route_has_throttle_middleware_attached(): void
    {
        $route = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->first(fn ($r) => $r->getName() === 'shop.stock-notify');
        $this->assertNotNull($route);
        $this->assertContains('throttle:shop-stock-notify', $route->gatherMiddleware());
    }

    public function test_shop_checkout_limiter_returns_5_per_minute(): void
    {
        $request = \Illuminate\Http\Request::create('/shop/checkout', 'POST');
        $reg = app(\Illuminate\Cache\RateLimiter::class);
        $limit = $reg->limiter('shop-checkout')($request);
        // Limit may be a single object or an array of objects.
        $limits = is_array($limit) ? $limit : [$limit];
        $this->assertCount(1, $limits);
        $this->assertSame(5, $limits[0]->maxAttempts);
    }

    public function test_shop_cart_limiter_returns_60_per_minute(): void
    {
        $request = \Illuminate\Http\Request::create('/shop/cart/add', 'POST');
        $reg = app(\Illuminate\Cache\RateLimiter::class);
        $limit = $reg->limiter('shop-cart')($request);
        $limits = is_array($limit) ? $limit : [$limit];
        $this->assertSame(60, $limits[0]->maxAttempts);
    }

    public function test_shop_review_limiter_is_keyed_by_product_slug(): void
    {
        $request1 = \Illuminate\Http\Request::create('/shop/red-shoe/reviews', 'POST');
        $request1->setRouteResolver(fn () => (new \Illuminate\Routing\Route(['POST'], '/shop/{slug}/reviews', []))
            ->bind($request1));
        $request1->route()->setParameter('slug', 'red-shoe');

        $request2 = \Illuminate\Http\Request::create('/shop/blue-shoe/reviews', 'POST');
        $request2->setRouteResolver(fn () => (new \Illuminate\Routing\Route(['POST'], '/shop/{slug}/reviews', []))
            ->bind($request2));
        $request2->route()->setParameter('slug', 'blue-shoe');

        $reg = app(\Illuminate\Cache\RateLimiter::class);
        $l1 = $reg->limiter('shop-review')($request1);
        $l2 = $reg->limiter('shop-review')($request2);
        $key1 = is_array($l1) ? $l1[0]->key : $l1->key;
        $key2 = is_array($l2) ? $l2[0]->key : $l2->key;
        $this->assertNotSame($key1, $key2, 'review limiter key must differ per product slug');
        $this->assertStringContainsString('p:red-shoe', $key1);
        $this->assertStringContainsString('p:blue-shoe', $key2);
    }

    public function test_authenticated_user_key_differs_from_guest_key(): void
    {
        $user = $this->user();
        $reg = app(\Illuminate\Cache\RateLimiter::class);

        $guestReq = \Illuminate\Http\Request::create('/shop/cart/add', 'POST');
        $guestReq->setLaravelSession(app('session.store'));
        $guestLimit = $reg->limiter('shop-cart')($guestReq);
        $guestKey = is_array($guestLimit) ? $guestLimit[0]->key : $guestLimit->key;

        $authReq = \Illuminate\Http\Request::create('/shop/cart/add', 'POST');
        $authReq->setLaravelSession(app('session.store'));
        $authReq->setUserResolver(fn () => $user);
        $authLimit = $reg->limiter('shop-cart')($authReq);
        $authKey = is_array($authLimit) ? $authLimit[0]->key : $authLimit->key;

        $this->assertStringStartsWith('g:', $guestKey, 'guest key must be prefixed with g:');
        $this->assertSame('u:' . $user->id, $authKey, 'auth key must be u:{id}');
    }

    /**
     * Pre-merge hardening: a request whose session has been started but
     * hasn't received an ID yet (or one without a Laravel session at all)
     * must never produce a key that ends with an empty session segment.
     * If it did, every anonymous request on the same IP would collapse
     * into a single bucket, which is _almost_ correct but loses the
     * "per browser session" granularity we promised.
     *
     * Contract: the resolved key must always end with a non-empty token,
     * and a request with no session must use the literal "no-sess".
     */
    public function test_guest_limiter_key_never_ends_with_empty_session_segment(): void
    {
        $reg = app(\Illuminate\Cache\RateLimiter::class);

        // Case 1: no session bound at all.
        $noSession = \Illuminate\Http\Request::create('/shop/cart/add', 'POST');
        $limit1 = $reg->limiter('shop-cart')($noSession);
        $key1 = is_array($limit1) ? $limit1[0]->key : $limit1->key;
        $this->assertStringEndsWith(':no-sess', $key1);
        $this->assertDoesNotMatchRegularExpression('/:$/', $key1);

        // Case 2: session bound but with an empty id. Laravel's real Store
        // auto-generates an id whenever a blank one is set, so we use a
        // tiny anonymous Session impl that genuinely returns ''.
        $emptyIdSession = \Illuminate\Http\Request::create('/shop/cart/add', 'POST');
        $blankSession = new class implements \Illuminate\Contracts\Session\Session {
            public function getName() { return 'blank'; }
            public function setName($name) {}
            public function getId() { return ''; }
            public function setId($id) {}
            public function start() { return true; }
            public function save() {}
            public function all() { return []; }
            public function exists($key) { return false; }
            public function missing($key) { return true; }
            public function has($key) { return false; }
            public function get($key, $default = null) { return $default; }
            public function pull($key, $default = null) { return $default; }
            public function put($key, $value = null) {}
            public function token() { return ''; }
            public function regenerateToken() {}
            public function remove($key) { return null; }
            public function forget($keys) {}
            public function flush() {}
            public function invalidate() { return true; }
            public function regenerate($destroy = false) { return true; }
            public function migrate($destroy = false) { return true; }
            public function isStarted() { return true; }
            public function previousUrl() { return null; }
            public function setPreviousUrl($url) {}
            public function flash($key, $value = true) {}
            public function getHandler() { return new \Illuminate\Session\ArraySessionHandler(120); }
            public function handlerNeedsRequest() { return false; }
            public function setRequestOnHandler($request) {}
        };
        $emptyIdSession->setLaravelSession($blankSession);
        $this->assertSame('', $blankSession->getId(), 'sanity: store id is empty');

        $limit2 = $reg->limiter('shop-cart')($emptyIdSession);
        $key2 = is_array($limit2) ? $limit2[0]->key : $limit2->key;
        $this->assertStringEndsWith(':no-sess', $key2);
        $this->assertDoesNotMatchRegularExpression('/:$/', $key2);

        // Case 3: proper session with id — must use that id (truncated <=32).
        $normalSession = \Illuminate\Http\Request::create('/shop/cart/add', 'POST');
        $store2 = new \Illuminate\Session\Store(
            'shop_test',
            new \Illuminate\Session\ArraySessionHandler(120)
        );
        $store2->setId(str_repeat('a', 40));
        $normalSession->setLaravelSession($store2);
        $limit3 = $reg->limiter('shop-cart')($normalSession);
        $key3 = is_array($limit3) ? $limit3[0]->key : $limit3->key;
        $this->assertStringContainsString(':' . str_repeat('a', 32), $key3);
        $this->assertDoesNotMatchRegularExpression('/:$/', $key3);
    }

    // =====================================================================
    // Phase 5.3B — captcha gate (controller-level)
    // =====================================================================

    public function test_validate_shop_captcha_returns_true_when_setting_off(): void
    {
        Setting::set('captcha_enabled', '0', 'captcha');
        Setting::set('shop_captcha_enabled', '0', 'captcha');
        $request = \Illuminate\Http\Request::create('/shop/checkout', 'POST');
        $this->assertTrue(app(\App\Services\CaptchaService::class)->validateShopCaptcha($request));
    }

    public function test_validate_shop_captcha_returns_true_for_authenticated_user_even_when_setting_on(): void
    {
        Setting::set('captcha_enabled', '1', 'captcha');
        Setting::set('shop_captcha_enabled', '1', 'captcha');
        Setting::set('captcha_type', 'persian_number', 'captcha');
        $user = $this->user();
        $this->actingAs($user);
        $request = \Illuminate\Http\Request::create('/shop/checkout', 'POST');
        // No captcha_answer provided, but user is authenticated → bypass.
        $this->assertTrue(app(\App\Services\CaptchaService::class)->validateShopCaptcha($request));
    }

    public function test_validate_shop_captcha_rejects_guest_without_answer_when_enabled(): void
    {
        Setting::set('captcha_enabled', '1', 'captcha');
        Setting::set('shop_captcha_enabled', '1', 'captcha');
        Setting::set('captcha_type', 'persian_number', 'captcha');
        // Seed an expected captcha code in session.
        Session::start();
        Session::put('captcha_persian_answer', '۱۲۳۴');
        $request = \Illuminate\Http\Request::create('/shop/checkout', 'POST', []);
        $request->setLaravelSession(app('session.store'));
        // No captcha_answer in request → expected to fail.
        $this->assertFalse(app(\App\Services\CaptchaService::class)->validateShopCaptcha($request));
    }

    public function test_validate_shop_captcha_accepts_correct_persian_answer(): void
    {
        Setting::set('captcha_enabled', '1', 'captcha');
        Setting::set('shop_captcha_enabled', '1', 'captcha');
        Setting::set('captcha_type', 'persian_number', 'captcha');
        Session::start();
        Session::put('captcha_persian_answer', '۵۶۷۸');
        $request = \Illuminate\Http\Request::create('/shop/checkout', 'POST', ['captcha_answer' => '5678']);
        $request->setLaravelSession(app('session.store'));
        $this->assertTrue(app(\App\Services\CaptchaService::class)->validateShopCaptcha($request));
    }

    public function test_get_shop_captcha_config_returns_disabled_for_authenticated_user(): void
    {
        Setting::set('captcha_enabled', '1', 'captcha');
        Setting::set('shop_captcha_enabled', '1', 'captcha');
        Setting::set('captcha_type', 'persian_number', 'captcha');
        $this->actingAs($this->user());
        $cfg = app(\App\Services\CaptchaService::class)->getShopCaptchaConfig();
        $this->assertFalse($cfg['enabled']);
    }

    public function test_get_shop_captcha_config_returns_enabled_for_guest_when_setting_on(): void
    {
        Setting::set('captcha_enabled', '1', 'captcha');
        Setting::set('shop_captcha_enabled', '1', 'captcha');
        Setting::set('captcha_type', 'persian_number', 'captcha');
        Session::start();
        $cfg = app(\App\Services\CaptchaService::class)->getShopCaptchaConfig();
        $this->assertTrue($cfg['enabled']);
        $this->assertSame('persian_number', $cfg['type']);
        $this->assertNotEmpty($cfg['persian_code']);
    }

    public function test_get_shop_captcha_config_returns_disabled_when_setting_off(): void
    {
        Setting::set('captcha_enabled', '1', 'captcha');
        Setting::set('shop_captcha_enabled', '0', 'captcha');
        $cfg = app(\App\Services\CaptchaService::class)->getShopCaptchaConfig();
        $this->assertFalse($cfg['enabled']);
    }

    // =====================================================================
    // Phase 5.3C — admin toggle persistence
    // =====================================================================

    public function test_admin_captcha_update_persists_shop_captcha_enabled_toggle(): void
    {
        $this->seedValidLicense();
        $admin = $this->user(admin: true);
        $this->actingAs($admin)
            ->from(route('admin.captcha.index'))
            ->post(route('admin.captcha.update'), [
                'captcha_enabled' => '1',
                'shop_captcha_enabled' => '1',
                'captcha_type' => 'persian_number',
            ])
            ->assertRedirect();

        Cache::forget('settings');
        $this->assertSame('1', (string) Setting::get('shop_captcha_enabled', '0'));
        $this->assertTrue(app(\App\Services\CaptchaService::class)->isShopCaptchaEnabled());
    }

    public function test_admin_captcha_update_clears_shop_captcha_when_unchecked(): void
    {
        $this->seedValidLicense();
        Setting::set('shop_captcha_enabled', '1', 'captcha');
        $admin = $this->user(admin: true);
        $this->actingAs($admin)
            ->post(route('admin.captcha.update'), [
                'captcha_enabled' => '1',
                'captcha_type' => 'persian_number',
                // shop_captcha_enabled intentionally omitted
            ])
            ->assertRedirect();

        Cache::forget('settings');
        $this->assertSame('0', (string) Setting::get('shop_captcha_enabled', '0'));
    }

    public function test_admin_captcha_index_view_exposes_shop_toggle_variables(): void
    {
        $this->seedValidLicense();
        $admin = $this->user(admin: true);
        $response = $this->actingAs($admin)->get(route('admin.captcha.index'));
        $response->assertOk();
        $response->assertViewHas('shopCaptchaEnabled');
        $response->assertViewHas('shopModuleEnabled');
        $response->assertSee('shop_captcha_enabled', false);
    }

    /**
     * Seed a current valid license matching the test host so that the
     * `license.valid` middleware on admin routes lets the request through.
     * Sets a far-future last_validated_at so syncWithPlatform is not triggered.
     */
    private function seedValidLicense(): License
    {
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';
        return License::create([
            'license_key' => 'W53-ADM-' . uniqid(),
            'domain' => $host,
            'expires_at' => now()->addYear(),
            'modules' => ['shop'],
            'platform_valid' => true,
            'last_validated_at' => now(),
        ]);
    }

    // =====================================================================
    // Backward compatibility
    // =====================================================================

    public function test_default_value_of_shop_captcha_enabled_setting_is_zero(): void
    {
        // No row inserted yet → defaults to '0'.
        $this->assertSame('0', (string) Setting::get('shop_captcha_enabled', '0'));
        $this->assertFalse(app(\App\Services\CaptchaService::class)->isShopCaptchaEnabled());
    }

    public function test_existing_captcha_helpers_unchanged_for_auth_form_path(): void
    {
        // Wave 5.3 must not have broken the auth_captcha_enabled toggle.
        Setting::set('captcha_enabled', '1', 'captcha');
        Setting::set('auth_captcha_enabled', '1', 'captcha');
        Setting::set('captcha_type', 'persian_number', 'captcha');
        $this->assertTrue(app(\App\Services\CaptchaService::class)->isAuthCaptchaEnabled());
        $cfg = app(\App\Services\CaptchaService::class)->getAuthCaptchaConfig();
        $this->assertTrue($cfg['enabled']);
        $this->assertSame('persian_number', $cfg['type']);
    }
}
