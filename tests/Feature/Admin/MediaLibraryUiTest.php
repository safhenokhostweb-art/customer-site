<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\CustomerMediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Media Library 2.0 — renders the real admin UI over the full HTTP/auth
 * stack (unlike CustomerMediaControllerTest, which bypasses middleware to
 * unit-test the controller/service contract). This is the guard against
 * Blade compile errors in the new JS-driven library view.
 */
class MediaLibraryUiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropIfExists('customer_media_usages');
        Schema::dropIfExists('customer_media');
        (require database_path('migrations/2026_09_10_100000_create_customer_media_tables.php'))->up();
        (require database_path('migrations/2026_09_15_100000_add_description_to_customer_media_table.php'))->up();
        Storage::fake('uploads');
        config(['site.platform_site_id' => 101]);
    }

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    public function test_index_and_show_pages_render_without_blade_errors(): void
    {
        $media = app(CustomerMediaService::class)->upload(UploadedFile::fake()->image('smoke.jpg'));

        $this->actingAs($this->admin())->get(route('admin.media.index'))
            ->assertOk()
            ->assertSee('کتابخانه رسانه')
            ->assertSee('mediaLibrary(', false);

        $this->actingAs($this->admin())->get(route('admin.media.show', $media->uuid))->assertOk();
    }
}
