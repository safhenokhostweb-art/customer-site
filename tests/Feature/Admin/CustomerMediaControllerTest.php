<?php

namespace Tests\Feature\Admin;

use App\Models\CustomerMedia;
use App\Services\CustomerMediaService;
use App\Http\Controllers\Admin\CustomerMediaController;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class CustomerMediaControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropIfExists('customer_media_usages');
        Schema::dropIfExists('customer_media');
        (require database_path('migrations/2026_09_10_100000_create_customer_media_tables.php'))->up();
        Storage::fake('uploads');
        config(['site.platform_site_id' => 101]);
        // The focused MEDIA schema intentionally excludes unrelated legacy
        // lifecycle tables. Keep HTTP routing while bypassing global middleware.
        $this->withoutMiddleware();
        view()->share('errors', new ViewErrorBag());
    }

    public function test_core_media_routes_are_not_module_or_license_gated_and_list_only_current_site_media(): void
    {
        $service = app(CustomerMediaService::class);
        $siteA = $service->upload(UploadedFile::fake()->image('site-a-needle.jpg'), ['title' => 'Site A needle']);
        config(['site.platform_site_id' => 202]);
        $service->upload(UploadedFile::fake()->image('site-b-needle.jpg'), ['title' => 'Site B needle']);
        config(['site.platform_site_id' => 101]);

        $route = app('router')->getRoutes()->getByName('admin.media.index');
        $this->assertNotContains('license.valid', $route->gatherMiddleware());
        $this->assertEmpty(array_filter($route->gatherMiddleware(), fn ($middleware) => str_starts_with($middleware, 'module:')));

        $view = app(CustomerMediaController::class)->index(Request::create('/admin/media', 'GET', ['q' => 'needle']));
        $items = $view->getData()['items'];
        $this->assertCount(1, $items);
        $this->assertSame($siteA->uuid, $items->first()->uuid);
    }

    public function test_upload_and_metadata_update_use_the_canonical_site_service(): void
    {
        $this->post(route('admin.media.store'), [
            'file' => UploadedFile::fake()->image('library.jpg'),
            'title' => 'Library image',
            'alt_text' => 'Initial alt',
        ])->assertRedirect(route('admin.media.index'));

        $media = CustomerMedia::firstOrFail();
        $this->patch(route('admin.media.update', $media->uuid), [
            'title' => 'Updated title', 'alt_text' => 'Updated alt', 'caption' => 'Updated caption',
        ])->assertRedirect(route('admin.media.show', $media->uuid));

        $this->assertDatabaseHas('customer_media', ['id' => $media->id, 'site_id' => 101, 'title' => 'Updated title']);
    }

    public function test_forged_cross_site_show_update_and_delete_requests_are_denied(): void
    {
        $service = app(CustomerMediaService::class);
        config(['site.platform_site_id' => 202]);
        $siteB = $service->upload(UploadedFile::fake()->image('private-b.jpg'));
        config(['site.platform_site_id' => 101]);

        $this->get(route('admin.media.show', $siteB->uuid))->assertNotFound();
        $this->patch(route('admin.media.update', $siteB->uuid), ['title' => 'forged'])->assertNotFound();
        $this->delete(route('admin.media.destroy', $siteB->uuid))->assertNotFound();

        $this->assertDatabaseHas('customer_media', ['id' => $siteB->id, 'site_id' => 202, 'deleted_at' => null]);
    }

    public function test_unused_current_site_media_can_be_deleted_but_used_media_is_preserved(): void
    {
        $service = app(CustomerMediaService::class);
        $unused = $service->upload(UploadedFile::fake()->image('unused.jpg'));
        $used = $service->upload(UploadedFile::fake()->image('used.jpg'));
        $service->registerUsage($used, 'theme_instance_revision', 7, '.pages.home.media_id');

        $this->delete(route('admin.media.destroy', $unused->uuid))->assertRedirect(route('admin.media.index'));
        $this->assertNotNull($unused->fresh()->deleted_at);

        $this->delete(route('admin.media.destroy', $used->uuid))
            ->assertRedirect(route('admin.media.index'))
            ->assertSessionHas('error');
        $this->assertNull($used->fresh()->deleted_at);
    }
}
