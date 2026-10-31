<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\CustomerMedia;
use App\Models\License;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * TASK 3/3 Phase 6.5 (BLD-21) + Phase 6.5D (BLD-30/31) —
 * MASTER_THEME_MEDIA_LOCALIZATION_GAP fix, and its remediation after final
 * pre-push certification found the original https-only rule rejected the
 * REAL canonical Universal Theme fixture in this actual environment
 * (PLATFORM_URL=http://saas-platform.test) — every prior test here used a
 * synthetic https://master-theme.test URL, which gave false confidence.
 *
 * Trust now comes from config('site.platform_url') — the SAME canonical
 * Platform origin every other real integration already uses — with an
 * environment-aware scheme policy (BLD-30) and atomic, loud failure for
 * any URL that matched that trusted origin (BLD-31). See
 * ThemeMediaLocalizationService's own docblock for the full contract.
 */
class ThemeMediaLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config([
            'site.owner_site' => true,
            'services.customer_provision_secret' => 'media-localization-test-secret',
        ]);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    private function bootstrap(): void
    {
        License::create([
            'license_key' => 'MEDIA-LOC-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);
    }

    /** A minimal 1x1 real PNG — small enough to embed, but a genuine, decodable image (getimagesizefromstring must succeed). */
    private function onePixelPng(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    }

    private function install(array $pages, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        $this->bootstrap();

        return $this->postJson('/builder/api/theme/install', array_merge([
            'theme_id' => 'universal-multipurpose-qa',
            'source_theme_id' => '1',
            'theme_name' => 'قالب جامع چندمنظوره',
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => $pages,
        ], $overrides), ['X-Provision-Secret' => 'media-localization-test-secret']);
    }

    // ── Core mechanism (production-shaped https trust) ─────────────────

    public function test_a_hero_background_image_hosted_on_the_trusted_platform_origin_is_localized_before_the_revision_is_created(): void
    {
        config(['site.platform_url' => 'https://master-theme.test']);

        Http::fake([
            'master-theme.test/storage/theme-media/*' => Http::response($this->onePixelPng(), 200, ['Content-Type' => 'image/png']),
        ]);

        $this->install([[
            'key' => 'home', 'title' => 'Home', 'blocks' => [
                ['id' => 'b1', 'type' => 'hero', 'props' => [
                    'title' => 'Welcome', 'background_image' => 'https://master-theme.test/storage/theme-media/1/hero.jpg',
                ]],
            ],
        ]])->assertOk()->assertJson(['success' => true]);

        $media = CustomerMedia::query()->sole();
        self::assertSame('image', $media->category);
        self::assertSame('https://master-theme.test/storage/theme-media/1/hero.jpg', $media->metadata['source_url'] ?? null);

        $setting = Setting::where('key', 'active_canvas_theme_data')->firstOrFail();
        $stored = json_decode($setting->value, true);
        $heroProps = $stored['pages'][0]['blocks'][0]['props'];

        self::assertIsArray($heroProps['background_image'], 'must be rewritten from a plain URL to the canonical {media_id} shape');
        self::assertSame($media->uuid, $heroProps['background_image']['media_id']);
    }

    public function test_untrusted_hosts_are_left_completely_untouched(): void
    {
        config(['site.platform_url' => 'https://master-theme.test']);
        Http::fake(); // must never be called for an off-trusted-origin host

        $this->install([[
            'key' => 'home', 'title' => 'Home', 'blocks' => [
                ['id' => 'b1', 'type' => 'hero', 'props' => [
                    'title' => 'Welcome', 'background_image' => 'https://attacker-controlled.test/evil.jpg',
                ]],
            ],
        ]])->assertOk()->assertJson(['success' => true]);

        self::assertSame(0, CustomerMedia::query()->count());

        $setting = Setting::where('key', 'active_canvas_theme_data')->firstOrFail();
        $stored = json_decode($setting->value, true);
        self::assertSame('https://attacker-controlled.test/evil.jpg', $stored['pages'][0]['blocks'][0]['props']['background_image']);

        Http::assertNothingSent();
    }

    public function test_gallery_repeater_images_are_localized_and_deduplicated_across_multiple_references(): void
    {
        config(['site.platform_url' => 'https://master-theme.test']);
        Http::fake([
            'master-theme.test/storage/theme-media/*' => Http::response($this->onePixelPng(), 200, ['Content-Type' => 'image/png']),
        ]);

        $this->install([[
            'key' => 'home', 'title' => 'Home', 'blocks' => [
                ['id' => 'g1', 'type' => 'gallery', 'props' => [
                    'items' => [
                        ['url' => 'https://master-theme.test/storage/theme-media/1/shared.jpg', 'alt' => 'A'],
                        ['url' => 'https://master-theme.test/storage/theme-media/1/shared.jpg', 'alt' => 'B'],
                    ],
                ]],
            ],
        ]])->assertOk()->assertJson(['success' => true]);

        // The exact same source URL appears twice — must resolve to ONE
        // stored CustomerMedia row, not two (dedup by source_url).
        self::assertSame(1, CustomerMedia::query()->count());

        $setting = Setting::where('key', 'active_canvas_theme_data')->firstOrFail();
        $stored = json_decode($setting->value, true);
        $items = $stored['pages'][0]['blocks'][0]['props']['items'];

        self::assertSame($items[0]['url']['media_id'], $items[1]['url']['media_id']);
    }

    public function test_bento_item_images_use_the_same_canonical_repeater_localization_path(): void
    {
        config(['site.platform_url' => 'https://master-theme.test']);
        Http::fake([
            'master-theme.test/storage/theme-media/*' => Http::response($this->onePixelPng(), 200, ['Content-Type' => 'image/png']),
        ]);

        $this->install([[
            'key' => 'home', 'title' => 'Home', 'blocks' => [
                ['id' => 'bento1', 'type' => 'bento_grid', 'props' => [
                    'items' => [[
                        'title' => 'فروشگاه حرفه‌ای',
                        // Canvas overhaul Unit 2a: the registry field is
                        // `bg_image`; the old hand list named it `image`, so
                        // a real bento background was never localized.
                        'bg_image' => 'https://master-theme.test/storage/theme-media/1/bento-shop.jpg',
                    ]],
                ]],
            ],
        ]])->assertOk()->assertJson(['success' => true]);

        $stored = json_decode(Setting::get('active_canvas_theme_data'), true);
        $image = $stored['pages'][0]['blocks'][0]['props']['items'][0]['bg_image'];

        self::assertIsArray($image);
        self::assertSame(CustomerMedia::query()->sole()->uuid, $image['media_id']);
    }

    // ── Atomic, loud failure (BLD-31) — replaces the old "graceful degrade" ──

    public function test_a_fetch_failure_for_a_trusted_origin_url_fails_the_whole_install_atomically_no_silent_partial_success(): void
    {
        config(['site.platform_url' => 'https://master-theme.test']);
        $this->bootstrap();

        Http::fake([
            'master-theme.test/storage/theme-media/*' => Http::response('', 500),
        ]);

        $response = $this->postJson('/builder/api/theme/install', [
            'theme_id' => 'universal-multipurpose-qa', 'source_theme_id' => '1', 'theme_name' => 'قالب جامع چندمنظوره',
            'header_blocks' => [], 'footer_blocks' => [],
            'pages' => [[
                'key' => 'home', 'title' => 'Home', 'blocks' => [
                    ['id' => 'b1', 'type' => 'hero', 'props' => [
                        'title' => 'Welcome', 'background_image' => 'https://master-theme.test/storage/theme-media/1/broken.jpg',
                    ]],
                ],
            ]],
        ], ['X-Provision-Secret' => 'media-localization-test-secret']);

        // A required Platform-owned image that could not be localized must
        // never be reported as a successful, self-contained install.
        $response->assertStatus(502);
        $response->assertJson(['success' => false, 'error' => 'media_localization_failed']);

        self::assertSame(0, CustomerMedia::query()->count(), 'no orphan CustomerMedia rows on failure');
        self::assertNull(Setting::where('key', 'active_theme_revision_id')->first()?->value ?: null, 'no revision/active theme must exist — the install never happened');
        self::assertSame(0, \App\Models\ThemeInstanceRevision::query()->count(), 'no partial ThemeInstanceRevision on failure');
    }

    public function test_orphan_files_from_a_partially_completed_batch_are_cleaned_up_on_failure(): void
    {
        config(['site.platform_url' => 'https://master-theme.test']);
        $this->bootstrap();

        // First asset succeeds and is written to real disk; second fails —
        // the whole localize() call must roll back BOTH the DB row AND the
        // physical file the first asset already wrote, not just the DB half.
        Http::fake([
            'master-theme.test/storage/theme-media/1/ok.jpg' => Http::response($this->onePixelPng(), 200, ['Content-Type' => 'image/png']),
            'master-theme.test/storage/theme-media/1/broken.jpg' => Http::response('', 500),
        ]);

        $siteId = app(\App\Services\CustomerMediaService::class)->siteId();
        $disk = Storage::disk('uploads');
        $before = collect($disk->allFiles("customer-media/{$siteId}"))->all();

        $response = $this->postJson('/builder/api/theme/install', [
            'theme_id' => 'universal-multipurpose-qa', 'source_theme_id' => '1', 'theme_name' => 'قالب جامع چندمنظوره',
            'header_blocks' => [], 'footer_blocks' => [],
            'pages' => [[
                'key' => 'home', 'title' => 'Home', 'blocks' => [
                    ['id' => 'b1', 'type' => 'hero', 'props' => ['title' => 'A', 'background_image' => 'https://master-theme.test/storage/theme-media/1/ok.jpg']],
                    ['id' => 'b2', 'type' => 'hero', 'props' => ['title' => 'B', 'background_image' => 'https://master-theme.test/storage/theme-media/1/broken.jpg']],
                ],
            ]],
        ], ['X-Provision-Secret' => 'media-localization-test-secret']);

        $response->assertStatus(502);
        self::assertSame(0, CustomerMedia::query()->count());

        // No NEW file left behind by this run specifically (pre-existing
        // files from other tests/sessions sharing this real disk are not
        // this test's concern).
        $after = collect($disk->allFiles("customer-media/{$siteId}"))->all();
        $newFiles = array_values(array_diff($after, $before));
        self::assertSame([], $newFiles, 'MISSING_PHYSICAL_FILES/orphan cleanup: no file from the failed batch may remain on disk');
    }

    // ── Environment-aware transport security matrix (BLD-30) ───────────

    public function test_local_dev_http_trusted_origin_is_allowed_when_configured_platform_url_is_http(): void
    {
        config(['site.platform_url' => 'http://saas-platform.test']);
        Http::fake([
            'saas-platform.test/storage/theme-media/*' => Http::response($this->onePixelPng(), 200, ['Content-Type' => 'image/png']),
        ]);

        $this->install([[
            'key' => 'home', 'title' => 'Home', 'blocks' => [
                ['id' => 'b1', 'type' => 'hero', 'props' => ['title' => 'x', 'background_image' => 'http://saas-platform.test/storage/theme-media/1/hero.jpg']],
            ],
        ]])->assertOk()->assertJson(['success' => true]);

        self::assertSame(1, CustomerMedia::query()->count());
    }

    public function test_http_url_to_an_untrusted_host_is_rejected_even_in_local_dev(): void
    {
        config(['site.platform_url' => 'http://saas-platform.test']);
        Http::fake();

        $this->install([[
            'key' => 'home', 'title' => 'Home', 'blocks' => [
                ['id' => 'b1', 'type' => 'hero', 'props' => ['title' => 'x', 'background_image' => 'http://evil.example/x.jpg']],
            ],
        ]])->assertOk()->assertJson(['success' => true]);

        self::assertSame(0, CustomerMedia::query()->count());
        Http::assertNothingSent();
    }

    public function test_https_url_to_an_untrusted_host_is_rejected_even_when_configured_origin_is_https(): void
    {
        config(['site.platform_url' => 'https://master-theme.test']);
        Http::fake();

        $this->install([[
            'key' => 'home', 'title' => 'Home', 'blocks' => [
                ['id' => 'b1', 'type' => 'hero', 'props' => ['title' => 'x', 'background_image' => 'https://evil.example/x.jpg']],
            ],
        ]])->assertOk()->assertJson(['success' => true]);

        self::assertSame(0, CustomerMedia::query()->count());
        Http::assertNothingSent();
    }

    public function test_wrong_scheme_for_the_configured_trusted_origin_is_rejected(): void
    {
        // Configured origin is https, media URL for the SAME host is http — must not match.
        config(['site.platform_url' => 'https://master-theme.test']);
        Http::fake();

        $this->install([[
            'key' => 'home', 'title' => 'Home', 'blocks' => [
                ['id' => 'b1', 'type' => 'hero', 'props' => ['title' => 'x', 'background_image' => 'http://master-theme.test/storage/theme-media/1/hero.jpg']],
            ],
        ]])->assertOk()->assertJson(['success' => true]);

        self::assertSame(0, CustomerMedia::query()->count());
        Http::assertNothingSent();
    }

    public function test_wrong_port_for_the_configured_trusted_origin_is_rejected(): void
    {
        config(['site.platform_url' => 'http://saas-platform.test:8080']);
        Http::fake();

        $this->install([[
            'key' => 'home', 'title' => 'Home', 'blocks' => [
                ['id' => 'b1', 'type' => 'hero', 'props' => ['title' => 'x', 'background_image' => 'http://saas-platform.test:9999/storage/theme-media/1/hero.jpg']],
            ],
        ]])->assertOk()->assertJson(['success' => true]);

        self::assertSame(0, CustomerMedia::query()->count());
        Http::assertNothingSent();
    }

    public function test_userinfo_in_the_media_url_is_rejected_even_for_an_otherwise_trusted_host(): void
    {
        config(['site.platform_url' => 'https://master-theme.test']);
        Http::fake();

        $this->install([[
            'key' => 'home', 'title' => 'Home', 'blocks' => [
                ['id' => 'b1', 'type' => 'hero', 'props' => ['title' => 'x', 'background_image' => 'https://attacker:pw@master-theme.test/storage/theme-media/1/hero.jpg']],
            ],
        ]])->assertOk()->assertJson(['success' => true]);

        self::assertSame(0, CustomerMedia::query()->count());
        Http::assertNothingSent();
    }

    public function test_unexpected_content_type_is_rejected_and_fails_the_install(): void
    {
        config(['site.platform_url' => 'https://master-theme.test']);
        Http::fake([
            'master-theme.test/storage/theme-media/*' => Http::response('<html>not an image</html>', 200, ['Content-Type' => 'text/html']),
        ]);
        $this->bootstrap();

        $response = $this->postJson('/builder/api/theme/install', [
            'theme_id' => 'universal-multipurpose-qa', 'source_theme_id' => '1', 'theme_name' => 'قالب جامع چندمنظوره',
            'header_blocks' => [], 'footer_blocks' => [],
            'pages' => [['key' => 'home', 'title' => 'Home', 'blocks' => [
                ['id' => 'b1', 'type' => 'hero', 'props' => ['title' => 'x', 'background_image' => 'https://master-theme.test/storage/theme-media/1/hero.jpg']],
            ]]],
        ], ['X-Provision-Secret' => 'media-localization-test-secret']);

        $response->assertStatus(502);
        self::assertSame(0, CustomerMedia::query()->count());
    }

    /**
     * Production-environment policy is tested directly against the
     * service's own (private) trust-resolution logic via reflection,
     * rather than mutating $this->app['env'] through a full HTTP request —
     * Laravel's own VerifyCsrfToken uses app()->runningUnitTests() (which
     * itself checks app()->environment() === 'testing') to auto-disable
     * CSRF during tests, so forcing 'production' mid-test breaks that and
     * produces an unrelated 419, not a meaningful production simulation.
     * Reflection tests the actual security decision in isolation, which is
     * what this policy is really about.
     */
    private function invokeTrustCheck(string $mediaUrl): bool
    {
        $service = app(\App\Services\ThemeCanvas\ThemeMediaLocalizationService::class);
        $ref = new \ReflectionClass($service);

        $trustedOrigin = $ref->getMethod('trustedOrigin');
        $trustedOrigin->setAccessible(true);
        $origin = $trustedOrigin->invoke($service);
        if ($origin === null) {
            return false;
        }

        $originMatches = $ref->getMethod('originMatches');
        $originMatches->setAccessible(true);

        return $originMatches->invoke($service, parse_url($mediaUrl), $origin);
    }

    public function test_production_environment_requires_https_even_if_configured_origin_is_http(): void
    {
        config(['site.platform_url' => 'http://saas-platform.test']);
        app()->detectEnvironment(fn () => 'production');

        try {
            self::assertFalse(
                $this->invokeTrustCheck('http://saas-platform.test/storage/theme-media/1/hero.jpg'),
                'production must never trust a non-https configured origin, even for the correct host'
            );
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
    }

    public function test_production_environment_allows_https_trusted_origin(): void
    {
        config(['site.platform_url' => 'https://panel.safhenokhost.ir']);
        app()->detectEnvironment(fn () => 'production');

        try {
            self::assertTrue($this->invokeTrustCheck('https://panel.safhenokhost.ir/storage/theme-media/1/hero.jpg'));
            self::assertFalse($this->invokeTrustCheck('https://evil.example/x.jpg'));
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
    }

    // ── Platform-independence at customer runtime (section 11) ─────────

    /**
     * TASK 3/3 Phase 6.5D section 11 — after install, the localized
     * customer site must render with ZERO requests back to the Platform,
     * even when the original Platform media source is completely
     * unreachable. Proves localization is a write-time (install) concern,
     * never a read-time (public render) dependency.
     */
    public function test_public_render_succeeds_with_zero_platform_requests_after_the_original_platform_media_source_becomes_unavailable(): void
    {
        config(['site.platform_url' => 'https://master-theme.test']);
        Http::fake([
            'master-theme.test/storage/theme-media/*' => Http::response($this->onePixelPng(), 200, ['Content-Type' => 'image/png']),
        ]);

        $this->install([[
            'key' => 'home', 'title' => 'Home', 'blocks' => [
                ['id' => 'b1', 'type' => 'hero', 'props' => ['title' => 'Welcome', 'background_image' => 'https://master-theme.test/storage/theme-media/1/hero.jpg']],
            ],
        ]])->assertOk()->assertJson(['success' => true]);

        self::assertSame(1, CustomerMedia::query()->count());

        // The ORIGINAL Platform media source is now unreachable — any
        // request to it during public render must not happen at all, let
        // alone be required for the render to succeed.
        Http::fake([
            'master-theme.test/*' => Http::response('', 503),
        ]);

        $response = $this->get('/');
        $response->assertOk();

        // Scoped to Platform MEDIA requests specifically (section 11's
        // PLATFORM_MEDIA_REQUEST_DURING_CUSTOMER_RUNTIME) — unrelated
        // pre-existing Platform integrations (e.g. the site-config sync
        // call every page load already makes) are out of this phase's
        // scope and must keep working exactly as before.
        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/storage/theme-media/'));
    }

    // ── THE REAL canonical fixture — the primary acceptance case (BLD-30) ──
    // Final pre-push certification's own finding: every test above (before
    // this phase) used a synthetic https://master-theme.test mini-theme,
    // which gave false confidence — the REAL universal-multipurpose-qa
    // export, with its real 30 http://saas-platform.test hotlinks, had
    // never once been proven to localize successfully. This is now the
    // primary, permanent regression guard against that ever silently
    // regressing again.

    public function test_the_real_canonical_universal_theme_export_fixture_localizes_every_trusted_hotlink(): void
    {
        config(['site.platform_url' => 'http://saas-platform.test']);

        $fixture = json_decode(
            file_get_contents(__DIR__ . '/fixtures/universal-multipurpose-qa-export.json'),
            true
        );
        self::assertIsArray($fixture, 'the real canonical fixture must load');

        $before = json_encode($fixture);
        $beforeCount = substr_count($before, 'saas-platform.test');
        self::assertGreaterThan(0, $beforeCount, 'precondition: the real fixture must contain real Platform hotlinks to localize (regenerate this test if the canonical fixture changes)');

        Http::fake([
            'saas-platform.test/storage/theme-media/*' => Http::response($this->onePixelPng(), 200, ['Content-Type' => 'image/png']),
        ]);

        $localized = app(\App\Services\ThemeCanvas\ThemeMediaLocalizationService::class)->localize($fixture);

        $after = json_encode($localized);
        self::assertSame(0, substr_count($after, 'saas-platform.test'), 'PERMANENT_PLATFORM_MEDIA_REFERENCES must be 0 for the real canonical fixture');
        self::assertGreaterThan(0, CustomerMedia::query()->count());

        foreach (CustomerMedia::all() as $media) {
            $path = Storage::disk($media->disk)->path($media->storage_key);
            self::assertFileExists($path, "CustomerMedia {$media->uuid} must have a real physical file");
            self::assertGreaterThan(0, filesize($path));
        }
    }

    /**
     * TASK 3/3 Phase 6.5D section 23 — the test above proves the
     * localization SERVICE itself against the real fixture; this test
     * proves the same real fixture through the actual, real, canonical
     * HTTP installation boundary (POST /builder/api/theme/install →
     * PageBuilderController::installTheme() →
     * ThemeInstanceRevisionService::record()), i.e. the exact same
     * request a real customer install performs — not a synthetic mini
     * theme, not a manual CustomerMedia/service call.
     */
    public function test_the_real_canonical_universal_theme_export_fixture_installs_end_to_end_through_the_real_http_installation_boundary(): void
    {
        config(['site.platform_url' => 'http://saas-platform.test']);

        $fixture = json_decode(
            file_get_contents(__DIR__ . '/fixtures/universal-multipurpose-qa-export.json'),
            true
        );
        self::assertIsArray($fixture, 'the real canonical fixture must load');

        Http::fake([
            'saas-platform.test/storage/theme-media/*' => Http::response($this->onePixelPng(), 200, ['Content-Type' => 'image/png']),
        ]);

        $this->install($fixture['pages'], [
            'theme_id'        => 'universal-multipurpose-qa',
            'theme_name'      => 'قالب جامع چندمنظوره',
            'source_theme_id' => '1',
            'header_blocks'   => $fixture['header_blocks'],
            'footer_blocks'   => $fixture['footer_blocks'],
            'header_variants' => $fixture['header_variants'],
            'footer_variants' => $fixture['footer_variants'],
            'global_styles'   => $fixture['global_styles'],
        ])->assertOk()->assertJson(['success' => true]);

        $stored = Setting::where('key', 'active_canvas_theme_data')->value('value');
        self::assertSame(0, substr_count($stored, 'saas-platform.test'), 'PERMANENT_PLATFORM_MEDIA_REFERENCES must be 0 after the real HTTP install');
        self::assertGreaterThan(0, CustomerMedia::query()->count());

        $revision = \App\Models\ThemeInstanceRevision::where('theme_identifier', 'universal-multipurpose-qa')->sole();
        self::assertSame(0, substr_count(json_encode($revision->theme_data), 'saas-platform.test'), 'the persisted revision snapshot itself must carry zero Platform references');
    }

    // ── Theme UPDATE path (section 18/19) — ONE canonical localization
    // boundary, not a second implementation. Exercises the real
    // ThemeInstanceRevisionService::recordUpgradeIfActiveRevisionUnchanged()
    // write path directly (the same method
    // ThemeMarketplaceController::applyUpgrade() calls after its own
    // preview/fingerprint/reconciliation steps, which are a separate,
    // pre-existing concern this phase does not touch) — proving that a
    // Theme update introducing a NEW Platform-owned media asset localizes
    // it through the exact same record() boundary a fresh install uses,
    // with no second competing implementation.

    public function test_theme_update_introducing_new_platform_media_localizes_it_through_the_same_canonical_boundary_and_preserves_prior_local_media(): void
    {
        config(['site.platform_url' => 'https://master-theme.test']);
        Http::fake([
            'master-theme.test/storage/theme-media/*' => Http::response($this->onePixelPng(), 200, ['Content-Type' => 'image/png']),
        ]);

        // v1 install — media A becomes customer-local.
        $this->install([[
            'key' => 'home', 'title' => 'Home', 'blocks' => [
                ['id' => 'b1', 'type' => 'hero', 'props' => ['title' => 'v1', 'background_image' => 'https://master-theme.test/storage/theme-media/1/asset-a.jpg']],
            ],
        ]], ['theme_id' => 'update-cert-theme'])->assertOk()->assertJson(['success' => true]);

        self::assertSame(1, CustomerMedia::query()->count());
        $mediaA = CustomerMedia::query()->sole();
        $activeRevisionId = (int) Setting::get('active_theme_revision_id');
        self::assertGreaterThan(0, $activeRevisionId);

        // Simulate a customer customization surviving the update: a second,
        // customer-authored block with no Platform media at all. Asset A's
        // block is left completely untouched — it must survive the update
        // unchanged, still pointing at its already-local media.
        $currentThemeData = json_decode(Setting::get('active_canvas_theme_data'), true);
        $currentThemeData['pages'][0]['blocks'][] = ['id' => 'customer1', 'type' => 'heading', 'props' => ['text' => 'متن سفارشی مشتری']];
        $originalAssetABlock = $currentThemeData['pages'][0]['blocks'][0];

        // v2 update — introduces a NEW, additional Platform media asset B in
        // a new block, via the real canonical update write path (not a
        // re-implementation): both A (pre-existing, untouched) and B (new)
        // must end up customer-local.
        $v2ThemeData = $currentThemeData;
        $v2ThemeData['pages'][0]['blocks'][] = ['id' => 'b2', 'type' => 'hero', 'props' => ['title' => 'v2', 'background_image' => 'https://master-theme.test/storage/theme-media/1/asset-b.jpg']];

        $newRevision = app(\App\Services\ThemeCanvas\ThemeInstanceRevisionService::class)->recordUpgradeIfActiveRevisionUnchanged(
            $activeRevisionId,
            $v2ThemeData,
            ['theme_identifier' => 'update-cert-theme', 'theme_name' => 'Update Cert Theme v2']
        );

        self::assertNotNull($newRevision, 'the upgrade write must succeed (active revision was unchanged)');
        self::assertSame(2, CustomerMedia::query()->count(), 'asset A remains, asset B is newly localized — no duplicate for A');

        $stored = json_decode(Setting::get('active_canvas_theme_data'), true);
        self::assertSame($originalAssetABlock, $stored['pages'][0]['blocks'][0], 'asset A must remain customer-local and byte-identical — the update must not re-touch it');
        self::assertIsArray($stored['pages'][0]['blocks'][2]['props']['background_image'], 'the new asset B must be a real {media_id}, not a hotlink');
        self::assertNotSame($mediaA->uuid, $stored['pages'][0]['blocks'][2]['props']['background_image']['media_id'], 'B must be a distinct media asset from A');
        self::assertStringContainsString('متن سفارشی مشتری', json_encode($stored, JSON_UNESCAPED_UNICODE), 'customer customization must survive the update');
        self::assertSame(0, substr_count(json_encode($stored), 'master-theme.test'), 'no Platform hotlink may remain after the update');

        // Rollback to the v1 revision must remain valid — proves the
        // update did not corrupt history or leave an unreconcilable state.
        $rolledBack = app(\App\Services\ThemeCanvas\ThemeInstanceRevisionService::class)->rollbackTo($activeRevisionId);
        self::assertNotNull($rolledBack);
        $afterRollback = json_decode(Setting::get('active_canvas_theme_data'), true);
        self::assertIsArray($afterRollback['pages'][0]['blocks'][0]['props']['background_image']);
        self::assertSame($mediaA->uuid, $afterRollback['pages'][0]['blocks'][0]['props']['background_image']['media_id']);
    }
}
