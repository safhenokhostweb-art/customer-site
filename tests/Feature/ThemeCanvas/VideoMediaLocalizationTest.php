<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\CustomerMedia;
use App\Models\License;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\RealWebmFixture;
use Tests\TestCase;

/**
 * Commercial Productization fase-4 — a real, confirmed gap found via an
 * actual local end-to-end theme install (not a mocked/faked test): the
 * `video` block's `url` prop is already in
 * CustomerMediaResolver::MEDIA_PROPS as real, intentional media needing
 * localization (this theme's own real local demo video, hero-intro.webm),
 * but ThemeMediaLocalizationService/CustomerMediaService's remote-import
 * path was image-only — so any theme with a real local demo video could
 * never actually be installed. Mirrors ThemeMediaLocalizationTest's own
 * real-fixture pattern (a genuine, decodable file, never synthetic bytes),
 * using the theme's actual real webm bytes so finfo_buffer()'s real
 * content-sniff genuinely exercises the same path production traffic does.
 */
class VideoMediaLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config([
            'site.owner_site' => true,
            'services.customer_provision_secret' => 'video-media-localization-test-secret',
            'site.platform_url' => 'https://master-theme.test',
        ]);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    private function install(array $pages): \Illuminate\Testing\TestResponse
    {
        License::create([
            'license_key' => 'VIDEO-LOC-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);

        return $this->postJson('/builder/api/theme/install', [
            'theme_id' => 'universal-multipurpose-qa',
            'source_theme_id' => '1',
            'theme_name' => 'قالب جامع چندمنظوره',
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => $pages,
        ], ['X-Provision-Secret' => 'video-media-localization-test-secret']);
    }

    public function test_a_real_local_demo_video_hosted_on_the_trusted_platform_origin_is_localized(): void
    {
        Http::fake([
            'master-theme.test/storage/theme-media/*' => Http::response(base64_decode(RealWebmFixture::base64()), 200, ['Content-Type' => 'video/webm']),
        ]);

        $this->install([[
            'key' => 'saas', 'title' => 'SaaS', 'blocks' => [
                ['id' => 'b1', 'type' => 'video', 'props' => [
                    'url' => 'https://master-theme.test/storage/theme-media/1/hero-intro.webm',
                ]],
            ],
        ]])->assertOk()->assertJson(['success' => true]);

        $media = CustomerMedia::query()->sole();
        self::assertSame('video', $media->category);
        self::assertNull($media->width);
        self::assertNull($media->height);
        self::assertSame('https://master-theme.test/storage/theme-media/1/hero-intro.webm', $media->metadata['source_url'] ?? null);

        $setting = Setting::where('key', 'active_canvas_theme_data')->firstOrFail();
        $stored = json_decode($setting->value, true);
        $videoProps = $stored['pages'][0]['blocks'][0]['props'];
        self::assertIsArray($videoProps['url'], 'must be rewritten from a plain URL to the canonical {media_id} shape');
        self::assertSame($media->uuid, $videoProps['url']['media_id']);
    }

    public function test_a_declared_video_content_type_that_does_not_match_the_real_bytes_is_rejected(): void
    {
        // A JPEG's real bytes, falsely declared as video/webm — the fetch
        // must fail loudly (atomic rollback), never silently accept a
        // mismatched declared header.
        Http::fake([
            'master-theme.test/storage/theme-media/*' => Http::response(
                base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAMCAgICAgMCAgIDAwMDBAYEBAQEBAgGBgUGCQgKCgkICQkKDA8MCgsOCwkJDRENDg8QEBEQCgwSExIQEw8QEBD/2wBDAQMDAwQDBAgEBAgQCwkLEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBD/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAj/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCdABmX/9k='),
                200,
                ['Content-Type' => 'video/webm']
            ),
        ]);

        $this->install([[
            'key' => 'saas', 'title' => 'SaaS', 'blocks' => [
                ['id' => 'b1', 'type' => 'video', 'props' => [
                    'url' => 'https://master-theme.test/storage/theme-media/1/hero-intro.webm',
                ]],
            ],
        ]])->assertStatus(502)->assertJson(['success' => false, 'error' => 'media_localization_failed']);

        self::assertSame(0, CustomerMedia::query()->count(), 'a failed localization must roll back, never leave a partial media row');
    }
}
