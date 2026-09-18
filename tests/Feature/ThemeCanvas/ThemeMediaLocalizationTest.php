<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\CustomerMedia;
use App\Models\License;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * TASK 3/3 Phase 6.5 (BLD-21) — MASTER_THEME_MEDIA_LOCALIZATION_GAP fix.
 *
 * Proves the real, canonical fix through the real install boundary: a
 * master-theme image hosted on the trusted allowlisted origin is fetched
 * ONCE, validated, stored into this site's own CustomerMediaService, and
 * the installed theme's block props are rewritten to a real {media_id}
 * reference BEFORE the (immutable) ThemeInstanceRevision is created — not
 * asserted against the service in isolation, but against the actual
 * /builder/api/theme/install HTTP endpoint saas-platform's ThemeInstallGateway
 * calls in production.
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
            'services.trusted_media_hosts' => ['master-theme.test'],
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

    private function install(array $pages): void
    {
        $this->bootstrap();

        Http::fake([
            'master-theme.test/storage/theme-media/*' => Http::response($this->onePixelPng(), 200, ['Content-Type' => 'image/png']),
        ]);

        $response = $this->postJson('/builder/api/theme/install', [
            'theme_id' => 'universal-multipurpose-qa',
            'source_theme_id' => '1',
            'theme_name' => 'قالب جامع چندمنظوره',
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => $pages,
        ], ['X-Provision-Secret' => 'media-localization-test-secret']);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    public function test_a_hero_background_image_hosted_on_the_trusted_platform_domain_is_localized_before_the_revision_is_created(): void
    {
        $this->install([[
            'key' => 'home', 'title' => 'Home', 'blocks' => [
                ['id' => 'b1', 'type' => 'hero', 'props' => [
                    'title' => 'Welcome', 'background_image' => 'https://master-theme.test/storage/theme-media/1/hero.jpg',
                ]],
            ],
        ]]);

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
        Http::fake(); // must never be called for an off-allowlist host

        $this->install([[
            'key' => 'home', 'title' => 'Home', 'blocks' => [
                ['id' => 'b1', 'type' => 'hero', 'props' => [
                    'title' => 'Welcome', 'background_image' => 'https://attacker-controlled.test/evil.jpg',
                ]],
            ],
        ]]);

        self::assertSame(0, CustomerMedia::query()->count());

        $setting = Setting::where('key', 'active_canvas_theme_data')->firstOrFail();
        $stored = json_decode($setting->value, true);
        self::assertSame('https://attacker-controlled.test/evil.jpg', $stored['pages'][0]['blocks'][0]['props']['background_image']);

        Http::assertNothingSent();
    }

    public function test_gallery_repeater_images_are_localized_and_deduplicated_across_multiple_references(): void
    {
        $this->install([[
            'key' => 'home', 'title' => 'Home', 'blocks' => [
                ['id' => 'g1', 'type' => 'gallery', 'props' => [
                    'items' => [
                        ['url' => 'https://master-theme.test/storage/theme-media/1/shared.jpg', 'alt' => 'A'],
                        ['url' => 'https://master-theme.test/storage/theme-media/1/shared.jpg', 'alt' => 'B'],
                    ],
                ]],
            ],
        ]]);

        // The exact same source URL appears twice — must resolve to ONE
        // stored CustomerMedia row, not two (dedup by source_url).
        self::assertSame(1, CustomerMedia::query()->count());

        $setting = Setting::where('key', 'active_canvas_theme_data')->firstOrFail();
        $stored = json_decode($setting->value, true);
        $items = $stored['pages'][0]['blocks'][0]['props']['items'];

        self::assertSame($items[0]['url']['media_id'], $items[1]['url']['media_id']);
    }

    public function test_a_fetch_failure_for_one_image_degrades_gracefully_and_does_not_block_install(): void
    {
        $this->bootstrap();

        Http::fake([
            'master-theme.test/storage/theme-media/*' => Http::response('', 500),
        ]);

        $response = $this->postJson('/builder/api/theme/install', [
            'theme_id' => 'universal-multipurpose-qa',
            'source_theme_id' => '1',
            'theme_name' => 'قالب جامع چندمنظوره',
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => [[
                'key' => 'home', 'title' => 'Home', 'blocks' => [
                    ['id' => 'b1', 'type' => 'hero', 'props' => [
                        'title' => 'Welcome', 'background_image' => 'https://master-theme.test/storage/theme-media/1/broken.jpg',
                    ]],
                ],
            ]],
        ], ['X-Provision-Secret' => 'media-localization-test-secret']);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        self::assertSame(0, CustomerMedia::query()->count());

        $setting = Setting::where('key', 'active_canvas_theme_data')->firstOrFail();
        $stored = json_decode($setting->value, true);
        self::assertSame('https://master-theme.test/storage/theme-media/1/broken.jpg', $stored['pages'][0]['blocks'][0]['props']['background_image']);
    }
}
