<?php

namespace Tests\Feature;

use App\Models\CustomerMedia;
use App\Models\License;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Customer customization experience — "logo" was requested as a branding
 * control but no site logo concept existed anywhere in the codebase (only a
 * browser favicon). This adds a `site_logo` Setting, an upload/remove flow
 * mirroring the existing favicon flow exactly, and wires it into both public
 * layouts via the shared <x-site-brand> component (falls back to the site
 * name text when no logo is uploaded, so every existing site is unaffected).
 *
 * TASK 3/3 Phase 6.5C (BLD-25) — logo/favicon upload converged onto the
 * canonical CustomerMediaService instead of an ad-hoc Storage call.
 * `site_logo`/`site_favicon` now store a CustomerMedia UUID, not a raw
 * storage path — updated below. `test_site_brand_component_renders_the_logo_image_when_uploaded`
 * intentionally still seeds a raw legacy path directly, unchanged, as the
 * real regression test for CustomerMediaService::resolveSiteAssetUrl()'s
 * backward-compatibility branch (any value uploaded before this phase).
 */
class SiteLogoBrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true]);
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    private function admin(): User
    {
        License::create([
            'license_key' => 'LOGO-TEST-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);

        return User::factory()->create(['is_admin' => true]);
    }

    public function test_admin_can_upload_a_site_logo(): void
    {
        $admin = $this->admin();
        $file = UploadedFile::fake()->image('logo.png', 200, 60);

        $response = $this->actingAs($admin)
            ->post(route('admin.settings.logo'), ['logo' => $file]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $uuid = Setting::get('site_logo');
        self::assertNotSame('', $uuid);
        $media = CustomerMedia::where('uuid', $uuid)->sole();
        self::assertSame('image', $media->category);
        self::assertSame('Site Logo', $media->title);
    }

    public function test_uploading_a_new_logo_replaces_the_old_media_and_removes_the_previous_one(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.settings.logo'), [
            'logo' => UploadedFile::fake()->image('logo.png', 200, 60),
        ]);
        $firstUuid = Setting::get('site_logo');

        $this->actingAs($admin)->post(route('admin.settings.logo'), [
            'logo' => UploadedFile::fake()->image('logo.jpg', 200, 60),
        ]);
        $secondUuid = Setting::get('site_logo');

        self::assertNotSame($firstUuid, $secondUuid);
        self::assertNotNull(CustomerMedia::where('uuid', $secondUuid)->first());
        self::assertNotNull(CustomerMedia::where('uuid', $firstUuid)->first()?->deleted_at, 'the replaced media must be cleaned up, not left orphaned forever');
    }

    public function test_admin_can_remove_the_logo(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.settings.logo'), [
            'logo' => UploadedFile::fake()->image('logo.png', 200, 60),
        ]);
        $uuid = Setting::get('site_logo');

        $response = $this->actingAs($admin)->delete(route('admin.settings.logo-remove'));

        $response->assertRedirect();
        $this->assertSame('', Setting::get('site_logo'));
        self::assertNotNull(CustomerMedia::where('uuid', $uuid)->first()?->deleted_at);
    }

    public function test_oversized_logo_is_rejected(): void
    {
        $admin = $this->admin();
        $file = UploadedFile::fake()->create('logo.png', 3000, 'image/png'); // 3MB > 2MB limit

        $response = $this->actingAs($admin)
            ->post(route('admin.settings.logo'), ['logo' => $file]);

        $response->assertSessionHasErrors('logo');
        $this->assertSame('', Setting::get('site_logo', ''));
    }

    /**
     * Security — SVG is intentionally excluded (no canonical sanitization
     * authority exists in this app for arbitrary user-supplied SVG, same
     * policy as CustomerMediaService). A raw SVG served straight from the
     * public disk would execute any embedded <script>/event handler if the
     * URL is opened directly — classic stored-XSS-via-SVG-upload. Every
     * active-content vector below must be rejected at the validation layer,
     * before any file ever reaches disk.
     */
    public static function maliciousSvgProvider(): array
    {
        return [
            'inline script' => ['<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'],
            'onload handler' => ['<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"></svg>'],
            'javascript: href' => ['<svg xmlns="http://www.w3.org/2000/svg"><a href="javascript:alert(1)"><text>x</text></a></svg>'],
            'external image reference' => ['<svg xmlns="http://www.w3.org/2000/svg"><image href="https://evil.example/track.png"/></svg>'],
            'foreignObject with script' => ['<svg xmlns="http://www.w3.org/2000/svg"><foreignObject><script xmlns="http://www.w3.org/1999/xhtml">alert(1)</script></foreignObject></svg>'],
            'plain benign svg (still rejected — no SVG format at all)' => ['<svg xmlns="http://www.w3.org/2000/svg"><circle r="5"/></svg>'],
        ];
    }

    #[DataProvider('maliciousSvgProvider')]
    public function test_svg_logo_upload_is_rejected_regardless_of_content(string $svgContent): void
    {
        $admin = $this->admin();
        $file = UploadedFile::fake()->createWithContent('logo.svg', $svgContent);

        $response = $this->actingAs($admin)
            ->post(route('admin.settings.logo'), ['logo' => $file]);

        $response->assertSessionHasErrors('logo');
        $this->assertSame('', Setting::get('site_logo', ''));
        Storage::disk('public')->assertMissing('logo/logo.svg');
    }

    #[DataProvider('maliciousSvgProvider')]
    public function test_svg_favicon_upload_is_rejected_regardless_of_content(string $svgContent): void
    {
        $admin = $this->admin();
        $file = UploadedFile::fake()->createWithContent('favicon.svg', $svgContent);

        $response = $this->actingAs($admin)
            ->post(route('admin.settings.favicon'), ['favicon' => $file]);

        $response->assertSessionHasErrors('favicon');
        $this->assertSame('', Setting::get('site_favicon', ''));
        Storage::disk('public')->assertMissing('favicon/favicon.svg');
    }

    // ── <x-site-brand> component ────────────────────────────────────────

    public function test_site_brand_component_falls_back_to_site_name_when_no_logo(): void
    {
        $html = \Illuminate\Support\Facades\Blade::render('<x-site-brand />');

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('<a href=', $html);
    }

    public function test_site_brand_component_renders_the_logo_image_when_uploaded(): void
    {
        Storage::disk('public')->put('logo/logo.png', 'fake-image-bytes');
        Setting::set('site_logo', 'logo/logo.png');

        $html = \Illuminate\Support\Facades\Blade::render('<x-site-brand />');

        $this->assertStringContainsString('<img', $html);
        $this->assertStringContainsString('logo/logo.png', $html);
    }
}
