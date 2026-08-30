<?php

namespace Tests\Feature;

use App\Models\License;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Customer customization experience — "logo" was requested as a branding
 * control but no site logo concept existed anywhere in the codebase (only a
 * browser favicon). This adds a `site_logo` Setting, an upload/remove flow
 * mirroring the existing favicon flow exactly, and wires it into both public
 * layouts via the shared <x-site-brand> component (falls back to the site
 * name text when no logo is uploaded, so every existing site is unaffected).
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
        $this->assertSame('logo/logo.png', Setting::get('site_logo'));
        Storage::disk('public')->assertExists('logo/logo.png');
    }

    public function test_uploading_a_new_logo_replaces_the_old_file(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.settings.logo'), [
            'logo' => UploadedFile::fake()->image('logo.png', 200, 60),
        ]);
        $this->actingAs($admin)->post(route('admin.settings.logo'), [
            'logo' => UploadedFile::fake()->image('logo.jpg', 200, 60),
        ]);

        $this->assertSame('logo/logo.jpg', Setting::get('site_logo'));
        Storage::disk('public')->assertMissing('logo/logo.png');
        Storage::disk('public')->assertExists('logo/logo.jpg');
    }

    public function test_admin_can_remove_the_logo(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.settings.logo'), [
            'logo' => UploadedFile::fake()->image('logo.png', 200, 60),
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.settings.logo-remove'));

        $response->assertRedirect();
        $this->assertSame('', Setting::get('site_logo'));
        Storage::disk('public')->assertMissing('logo/logo.png');
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
