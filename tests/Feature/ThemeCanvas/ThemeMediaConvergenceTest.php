<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\CustomerMedia;
use App\Models\License;
use App\Models\ThemeInstanceRevision;
use App\Models\User;
use App\Services\CustomerMediaService;
use App\Services\ThemeCanvas\ThemeInstanceRevisionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * MEDIA-4: Theme Builder convergence onto the canonical Core Media Library.
 * CustomerMediaService remains the sole write authority; the Theme Builder
 * media picker (ThemeDesignMediaController) is a thin adapter over it, and
 * a saved theme's {media_id} references are validated as current-site-owned
 * by the existing ThemeInstanceRevisionService::record() -> registerThemeRevisionUsages()
 * -> findForCurrentSite() path — no new validation code was needed.
 */
class ThemeMediaConvergenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true, 'site.platform_site_id' => 401]);
        Storage::fake('uploads');
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
            'license_key' => 'MEDIA4-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);

        return User::factory()->create(['is_admin' => true]);
    }

    private function upload(): CustomerMedia
    {
        return app(CustomerMediaService::class)->upload(UploadedFile::fake()->image('photo.jpg'));
    }

    // ── Save-time canonical identity validation ───────────────────────────

    public function test_theme_save_with_canonical_media_id_saves_and_registers_usage(): void
    {
        $media = $this->upload();

        $revision = app(ThemeInstanceRevisionService::class)->record(
            ['pages' => ['home' => ['blocks' => [
                ['id' => 'img1', 'type' => 'image', 'props' => ['url' => ['media_id' => $media->uuid]]],
            ]]]],
            ThemeInstanceRevision::TYPE_INSTALL,
            ['theme_identifier' => 'media4-canonical'],
        );

        $this->assertNotNull($revision->id);
        $this->assertDatabaseHas('customer_media_usages', [
            'customer_media_id' => $media->id,
            'consumer_type' => 'theme_instance_revision',
            'consumer_id' => $revision->id,
        ]);
    }

    public function test_theme_save_with_legacy_url_string_still_saves_without_usage_tracking(): void
    {
        $revision = app(ThemeInstanceRevisionService::class)->record(
            ['pages' => ['home' => ['blocks' => [
                ['id' => 'img1', 'type' => 'image', 'props' => ['url' => 'https://legacy.example.com/old.jpg']],
            ]]]],
            ThemeInstanceRevision::TYPE_INSTALL,
            ['theme_identifier' => 'media4-legacy'],
        );

        $this->assertNotNull($revision->id);
        $this->assertSame(0, \App\Models\CustomerMediaUsage::where('consumer_id', $revision->id)->count());
    }

    public function test_theme_save_with_forged_foreign_media_id_is_rejected_and_nothing_lands(): void
    {
        $foreignMedia = $this->upload();
        config(['site.platform_site_id' => 402]); // switch to a different site before saving

        $threw = false;
        try {
            app(ThemeInstanceRevisionService::class)->record(
                ['pages' => ['home' => ['blocks' => [
                    ['id' => 'img1', 'type' => 'image', 'props' => ['url' => ['media_id' => $foreignMedia->uuid]]],
                ]]]],
                ThemeInstanceRevision::TYPE_INSTALL,
                ['theme_identifier' => 'media4-forged'],
            );
        } catch (ModelNotFoundException) {
            $threw = true;
        }

        $this->assertTrue($threw, 'a foreign-site media_id must be rejected, not silently resolved by global uuid lookup');
        $this->assertSame(0, ThemeInstanceRevision::where('theme_identifier', 'media4-forged')->count(), 'the rejected revision must not have landed (transactional)');
    }

    public function test_theme_save_with_unknown_media_id_is_rejected(): void
    {
        $threw = false;
        try {
            app(ThemeInstanceRevisionService::class)->record(
                ['pages' => ['home' => ['blocks' => [
                    ['id' => 'img1', 'type' => 'image', 'props' => ['url' => ['media_id' => (string) Str::uuid()]]],
                ]]]],
                ThemeInstanceRevision::TYPE_INSTALL,
                ['theme_identifier' => 'media4-unknown'],
            );
        } catch (ModelNotFoundException) {
            $threw = true;
        }

        $this->assertTrue($threw);
        $this->assertSame(0, ThemeInstanceRevision::where('theme_identifier', 'media4-unknown')->count());
    }

    public function test_theme_save_with_soft_deleted_media_id_is_rejected(): void
    {
        $media = $this->upload();
        $media->deleted_at = now();
        $media->save();

        $threw = false;
        try {
            app(ThemeInstanceRevisionService::class)->record(
                ['pages' => ['home' => ['blocks' => [
                    ['id' => 'img1', 'type' => 'image', 'props' => ['url' => ['media_id' => $media->uuid]]],
                ]]]],
                ThemeInstanceRevision::TYPE_INSTALL,
                ['theme_identifier' => 'media4-deleted'],
            );
        } catch (ModelNotFoundException) {
            $threw = true;
        }

        $this->assertTrue($threw, 'a soft-deleted media_id must never be resurrected by a Builder save');
    }

    // ── ThemeDesignMediaController: compatibility adapter, not a second writer ──

    public function test_upload_through_theme_design_media_controller_creates_a_real_customer_media_record(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->postJson(route('admin.theme-design.media-upload'), [
            'file' => UploadedFile::fake()->image('builder-upload.jpg'),
        ])->assertOk();

        $uuid = $response->json('uuid');
        $this->assertNotEmpty($uuid);
        $this->assertDatabaseHas('customer_media', ['uuid' => $uuid, 'site_id' => 401]);

        // No parallel/legacy theme-media writer: nothing landed under the
        // old flat public disk directory the pre-MEDIA-4 controller used.
        Storage::disk('public')->assertDirectoryEmpty('theme-media');
    }

    public function test_theme_design_media_controller_rejects_svg_exactly_like_the_main_library(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->postJson(route('admin.theme-design.media-upload'), [
            'file' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, CustomerMedia::count());
    }

    public function test_theme_design_media_index_only_lists_current_site_media(): void
    {
        $admin = $this->admin();
        $mine = $this->upload();
        config(['site.platform_site_id' => 403]);
        $foreign = $this->upload();
        config(['site.platform_site_id' => 401]);

        $response = $this->actingAs($admin)->getJson(route('admin.theme-design.media-index'))->assertOk();

        $uuids = collect($response->json('images'))->pluck('uuid');
        $this->assertTrue($uuids->contains($mine->uuid));
        $this->assertFalse($uuids->contains($foreign->uuid));
    }

    public function test_theme_design_media_index_supports_search_and_category_filter(): void
    {
        $admin = $this->admin();
        $service = app(CustomerMediaService::class);
        $service->upload(UploadedFile::fake()->image('unique-needle.jpg'), ['title' => 'needle-title']);
        $service->upload(UploadedFile::fake()->image('haystack.jpg'), ['title' => 'other']);

        $bySearch = $this->actingAs($admin)->getJson(route('admin.theme-design.media-index', ['q' => 'needle-title']))->assertOk();
        $this->assertCount(1, $bySearch->json('images'));

        $byCategory = $this->actingAs($admin)->getJson(route('admin.theme-design.media-index', ['category' => 'document']))->assertOk();
        $this->assertCount(0, $byCategory->json('images'));
    }

    public function test_theme_design_media_destroy_respects_in_use_protection(): void
    {
        $admin = $this->admin();
        $media = $this->upload();
        app(ThemeInstanceRevisionService::class)->record(
            ['pages' => ['home' => ['blocks' => [
                ['id' => 'img1', 'type' => 'image', 'props' => ['url' => ['media_id' => $media->uuid]]],
            ]]]],
            ThemeInstanceRevision::TYPE_INSTALL,
            ['theme_identifier' => 'media4-used'],
        );

        $response = $this->actingAs($admin)->deleteJson(route('admin.theme-design.media-destroy'), ['uuid' => $media->uuid]);

        $response->assertStatus(422);
        $this->assertDatabaseHas('customer_media', ['id' => $media->id, 'deleted_at' => null]);
    }

    // ── hero.background_image: previously an exposed media control with no
    //    runtime effect (audit finding); now applies real CSS ──────────────

    public function test_hero_background_image_resolves_and_renders_as_real_css(): void
    {
        $media = $this->upload();
        $service = app(CustomerMediaService::class);
        $url = $service->publicUrl($media);

        $renderer = app(\App\Services\ThemeCanvas\ThemeCanvasRenderer::class);
        $renderer->setThemeData(['pages' => []]);
        $html = $renderer->renderBlock([
            'id' => 'hero1',
            'type' => 'hero',
            'props' => ['title' => 'عنوان', 'background_image' => ['media_id' => $media->uuid]],
        ]);

        $this->assertStringContainsString('background-image:url(' . $url . ')', $html);
    }

    public function test_hero_background_image_omits_style_when_unset(): void
    {
        $renderer = app(\App\Services\ThemeCanvas\ThemeCanvasRenderer::class);
        $renderer->setThemeData(['pages' => []]);
        $html = $renderer->renderBlock([
            'id' => 'hero2',
            'type' => 'hero',
            'props' => ['title' => 'عنوان'],
        ]);

        $this->assertStringNotContainsString('background-image', $html);
    }
}
