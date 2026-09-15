<?php

namespace Tests\Feature\Admin;

use App\Models\CustomerMedia;
use App\Services\CustomerMediaService;
use App\Http\Controllers\Admin\CustomerMediaController;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        (require database_path('migrations/2026_09_15_100000_add_description_to_customer_media_table.php'))->up();
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

    public function test_multi_file_upload_processes_each_file_independently_and_reports_per_file_status(): void
    {
        $response = $this->postJson(route('admin.media.store'), [
            'files' => [
                UploadedFile::fake()->image('good-1.jpg'),
                UploadedFile::fake()->create('bad.exe', 10, 'application/x-msdownload'),
                UploadedFile::fake()->image('good-2.png'),
            ],
        ]);

        $response->assertOk();
        $results = $response->json('results');
        $this->assertCount(3, $results);
        $statuses = collect($results)->pluck('status')->all();
        $this->assertSame(['success', 'failed', 'success'], $statuses);
        $this->assertSame(2, CustomerMedia::query()->count());
    }

    public function test_index_json_supports_usage_filter_and_sort(): void
    {
        $service = app(CustomerMediaService::class);
        $unused = $service->upload(UploadedFile::fake()->image('lonely.jpg'));
        $used = $service->upload(UploadedFile::fake()->image('busy.jpg'));
        $service->registerUsage($used, 'theme_instance_revision', 1, '.media_id');

        $response = $this->getJson(route('admin.media.index', ['usage' => 'unused']));
        $response->assertOk();
        $items = $response->json('items');
        $this->assertCount(1, $items);
        $this->assertSame($unused->uuid, $items[0]['uuid']);
    }

    public function test_replace_preserves_identity_and_rejects_incompatible_family(): void
    {
        $service = app(CustomerMediaService::class);
        $media = $service->upload(UploadedFile::fake()->image('before.jpg', 80, 80));

        $ok = $this->postJson(route('admin.media.replace', $media->uuid), [
            'file' => UploadedFile::fake()->image('after.png', 120, 90),
        ]);
        $ok->assertOk();
        $this->assertSame($media->uuid, $ok->json('item.uuid'));
        $this->assertSame(120, $ok->json('item.width'));

        $bad = $this->postJson(route('admin.media.replace', $media->uuid), [
            'file' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ]);
        $bad->assertStatus(422);
    }

    public function test_edit_image_rotate_preserves_identity(): void
    {
        $service = app(CustomerMediaService::class);
        $media = $service->upload(UploadedFile::fake()->image('rotate-me.jpg', 200, 100));

        $response = $this->postJson(route('admin.media.edit-image', $media->uuid), [
            'operation' => 'rotate', 'degrees' => 90,
        ]);

        $response->assertOk();
        $this->assertSame($media->uuid, $response->json('item.uuid'));
        $this->assertSame(100, $response->json('item.width'));
        $this->assertSame(200, $response->json('item.height'));
    }

    public function test_batch_delete_is_itemized_used_media_blocks_only_that_item(): void
    {
        $service = app(CustomerMediaService::class);
        $unused = $service->upload(UploadedFile::fake()->image('batch-unused.jpg'));
        $used = $service->upload(UploadedFile::fake()->image('batch-used.jpg'));
        $service->registerUsage($used, 'theme_instance_revision', 1, '.media_id');

        $response = $this->postJson(route('admin.media.batch-destroy'), [
            'uuids' => [$unused->uuid, $used->uuid, 'not-a-real-uuid'],
        ]);

        $response->assertOk();
        $byUuid = collect($response->json('results'))->keyBy('uuid');
        $this->assertSame('deleted', $byUuid[$unused->uuid]['status']);
        $this->assertSame('blocked', $byUuid[$used->uuid]['status']);
        $this->assertSame('failed', $byUuid['not-a-real-uuid']['status']);
        $this->assertNotNull($unused->fresh()->deleted_at);
        $this->assertNull($used->fresh()->deleted_at);
    }

    /**
     * Performance certification (Media Library 2.0, §23): the main library
     * page's query count must not scale with the number of media rows —
     * withCount('usages') is one aggregated join, publicUrl() is pure
     * string construction, never a query. 20 rows here vs. the 2-row
     * baseline elsewhere in this file prove the count is flat, not linear.
     */
    public function test_index_query_count_does_not_scale_with_row_count(): void
    {
        $service = app(CustomerMediaService::class);
        for ($i = 0; $i < 20; $i++) {
            $media = $service->upload(UploadedFile::fake()->image("perf-{$i}.jpg"));
            if ($i % 3 === 0) {
                $service->registerUsage($media, 'theme_instance_revision', $i + 1, ".media_id.{$i}");
            }
        }

        DB::enableQueryLog();
        $this->getJson(route('admin.media.index'))->assertOk();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(4, $queryCount, 'Media library index must not run one query per row (N+1).');
    }

    public function test_forged_cross_site_replace_edit_and_batch_delete_are_denied(): void
    {
        $service = app(CustomerMediaService::class);
        config(['site.platform_site_id' => 202]);
        $siteB = $service->upload(UploadedFile::fake()->image('cross-site.jpg'));
        config(['site.platform_site_id' => 101]);

        $this->postJson(route('admin.media.replace', $siteB->uuid), [
            'file' => UploadedFile::fake()->image('x.jpg'),
        ])->assertNotFound();

        $this->postJson(route('admin.media.edit-image', $siteB->uuid), [
            'operation' => 'rotate', 'degrees' => 90,
        ])->assertNotFound();

        $batch = $this->postJson(route('admin.media.batch-destroy'), ['uuids' => [$siteB->uuid]]);
        $batch->assertOk();
        $this->assertSame('failed', $batch->json('results.0.status'));
        $this->assertNull($siteB->fresh()->deleted_at);
    }
}
