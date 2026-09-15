<?php

namespace Tests\Feature;

use App\Models\CustomerMedia;
use App\Services\CustomerMediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class CustomerMediaServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // This focused suite deliberately executes only MEDIA-1/2's schema.
        // The application's historical migration set contains MySQL-specific
        // SQL and is not a valid SQLite portability test surface.
        Schema::dropIfExists('customer_media_usages');
        Schema::dropIfExists('customer_media');
        (require database_path('migrations/2026_09_10_100000_create_customer_media_tables.php'))->up();
        (require database_path('migrations/2026_09_15_100000_add_description_to_customer_media_table.php'))->up();
        Storage::fake('uploads');
        config(['site.platform_site_id' => 101]);
    }

    /**
     * BASELINE ZERO PASS (test-pollution fix): this class deliberately
     * doesn't use RefreshDatabase (see setUp()'s own comment — its raw
     * migration invocation is a real MySQL-only schema, not something
     * RefreshDatabase's transaction wrapping was doing anyway here) so
     * every row it inserts is a real, committed INSERT with no wrapping
     * transaction to roll it back. Left uncleaned, that data (e.g.
     * site-a.jpg/site-b.jpg from the tenant-isolation test) survived as
     * real rows for the rest of any full suite run — exactly what made
     * Tests\Feature\ThemeCanvas\ThemeMediaConvergenceTest's
     * `CustomerMedia::count()` assertion fail only in full-suite context,
     * never in isolation. Deleting (not dropping) the rows here keeps the
     * schema itself intact for every other RefreshDatabase-based test that
     * expects these tables to already exist from the one-time migrate:fresh
     * RefreshDatabase itself ran earlier in the process.
     */
    protected function tearDown(): void
    {
        \Illuminate\Support\Facades\DB::table('customer_media_usages')->delete();
        \Illuminate\Support\Facades\DB::table('customer_media')->delete();
        parent::tearDown();
    }

    public function test_upload_creates_a_tenant_scoped_canonical_record_and_safe_key(): void
    {
        $media = app(CustomerMediaService::class)->upload(
            UploadedFile::fake()->image('hero.png', 640, 480),
            ['title' => 'Hero', 'alt_text' => 'Hero image']
        );

        $this->assertSame(101, $media->site_id);
        $this->assertSame('image', $media->category);
        $this->assertMatchesRegularExpression('#^customer-media/101/\d{4}/\d{2}/[\w-]+\.png$#', $media->storage_key);
        $this->assertNotEmpty($media->uuid);
        Storage::disk('uploads')->assertExists($media->storage_key);
    }

    public function test_unsafe_svg_is_rejected_until_a_canonical_sanitizer_exists(): void
    {
        $this->expectException(ValidationException::class);
        app(CustomerMediaService::class)->upload(
            UploadedFile::fake()->createWithContent('unsafe.svg', '<svg><script>alert(1)</script></svg>')
        );
    }

    public function test_media_is_server_side_tenant_scoped(): void
    {
        $service = app(CustomerMediaService::class);
        $media = $service->upload(UploadedFile::fake()->image('isolated.jpg'));
        config(['site.platform_site_id' => 202]);

        $this->expectException(ModelNotFoundException::class);
        $service->findForCurrentSite($media->uuid);
    }

    public function test_unused_media_is_soft_deleted_then_physically_purged(): void
    {
        $service = app(CustomerMediaService::class);
        $media = $service->upload(UploadedFile::fake()->image('unused.jpg'));
        $key = $media->storage_key;

        $service->deleteUnused($media);

        $this->assertNotNull($media->fresh()->deleted_at);
        $this->assertNotNull($media->fresh()->purged_at);
        Storage::disk('uploads')->assertMissing($key);
    }

    public function test_failed_purge_is_recoverable_and_a_later_retry_succeeds(): void
    {
        $service = app(CustomerMediaService::class);
        $media = $service->upload(UploadedFile::fake()->image('retry.jpg'));
        $media->deleted_at = now();
        $media->disk = 'missing-customer-media-test-disk';
        $media->save();

        try {
            $service->purge($media);
            $this->fail('A missing storage disk must not be treated as a successful purge.');
        } catch (\InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }
        $this->assertNull($media->fresh()->purged_at);

        $media = $media->fresh();
        $media->disk = 'uploads';
        $media->save();
        $service->purge($media->fresh());

        $this->assertNotNull($media->fresh()->purged_at);
    }

    public function test_used_media_cannot_be_deleted(): void
    {
        $service = app(CustomerMediaService::class);
        $media = $service->upload(UploadedFile::fake()->image('used.jpg'));
        $service->registerUsage($media, 'theme_instance_revision', 7, '.pages.home.blocks.0.props.image.media_id');

        $this->expectException(\LogicException::class);
        $service->deleteUnused($media);
    }

    public function test_same_logical_usage_is_idempotent_and_historical_usage_blocks_deletion(): void
    {
        $service = app(CustomerMediaService::class);
        $media = $service->upload(UploadedFile::fake()->image('rollback.jpg'));

        $service->registerUsage($media, 'theme_instance_revision', 1, '.pages.home.blocks.block_hero.props.media_id');
        $service->registerUsage($media, 'theme_instance_revision', 1, '.pages.home.blocks.block_hero.props.media_id');

        $this->assertCount(1, $service->usages($media));
        $this->assertTrue($service->isUsed($media));
        $this->expectException(\LogicException::class);
        $service->deleteUnused($media);
    }

    public function test_usage_identity_distinguishes_every_exact_component_without_prefix_collisions(): void
    {
        $service = app(CustomerMediaService::class);
        $first = $service->upload(UploadedFile::fake()->image('first.jpg'));
        $second = $service->upload(UploadedFile::fake()->image('second.jpg'));
        $base = str_repeat('p', 500);

        $service->registerUsage($first, 'revision', 1, $base . 'a');
        $service->registerUsage($first, 'revision', 1, $base . 'a');
        $service->registerUsage($first, 'revision-other', 1, $base . 'a');
        $service->registerUsage($first, 'revision', 2, $base . 'a');
        $service->registerUsage($first, 'revision', 1, $base . 'b');
        $service->registerUsage($second, 'revision', 1, $base . 'a');

        $this->assertCount(4, $service->usages($first));
        $this->assertCount(1, $service->usages($second));
    }

    public function test_nested_theme_media_ids_are_registered_against_immutable_revision(): void
    {
        $service = app(CustomerMediaService::class);
        $media = $service->upload(UploadedFile::fake()->image('nested.jpg'));

        $service->registerThemeRevisionUsages(99, [
            'pages' => [
                'home' => [
                    'blocks' => [[
                        'props' => ['gallery' => [['media_id' => $media->uuid]]],
                    ]],
                ],
            ],
        ]);

        $this->assertDatabaseHas('customer_media_usages', [
            'customer_media_id' => $media->id,
            'consumer_type' => 'theme_instance_revision',
            'consumer_id' => 99,
        ]);
    }

    public function test_cross_site_media_operations_and_forged_revision_references_fail_closed(): void
    {
        $service = app(CustomerMediaService::class);
        $siteAMedia = $service->upload(UploadedFile::fake()->image('site-a.jpg'));

        config(['site.platform_site_id' => 202]);
        $siteBMedia = $service->upload(UploadedFile::fake()->image('site-b.jpg'));
        $this->assertSame(202, $service->findForCurrentSite($siteBMedia->uuid)->site_id);

        config(['site.platform_site_id' => 101]);
        foreach ([
            fn () => $service->findForCurrentSite($siteBMedia->uuid),
            fn () => $service->registerUsage($siteBMedia, 'theme_instance_revision', 22, '.forged.media_id'),
            fn () => $service->deleteUnused($siteBMedia),
            fn () => $service->purge($siteBMedia),
            fn () => $service->registerThemeRevisionUsages(22, ['pages' => ['home' => ['media_id' => $siteBMedia->uuid]]]),
        ] as $operation) {
            try {
                $operation();
                $this->fail('Cross-site media operation was accepted.');
            } catch (NotFoundHttpException|ModelNotFoundException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertSame(101, $service->findForCurrentSite($siteAMedia->uuid)->site_id);
    }

    public function test_metadata_update_persists_description_alongside_title_alt_and_caption(): void
    {
        $service = app(CustomerMediaService::class);
        $media = $service->upload(UploadedFile::fake()->image('meta.jpg'));

        $service->updateMetadata($media, [
            'title' => 'Title', 'alt_text' => 'Alt', 'caption' => 'Caption', 'description' => 'Full description',
        ]);

        $this->assertDatabaseHas('customer_media', [
            'id' => $media->id, 'title' => 'Title', 'alt_text' => 'Alt',
            'caption' => 'Caption', 'description' => 'Full description',
        ]);
    }

    public function test_library_query_supports_usage_filter_and_sort_without_scaling_per_row(): void
    {
        $service = app(CustomerMediaService::class);
        $unused = $service->upload(UploadedFile::fake()->image('z-unused.jpg'));
        $used = $service->upload(UploadedFile::fake()->image('a-used.jpg'));
        $service->registerUsage($used, 'theme_instance_revision', 1, '.media_id');

        $unusedOnly = $service->paginateForCurrentSite(usage: 'unused');
        $this->assertCount(1, $unusedOnly);
        $this->assertSame($unused->uuid, $unusedOnly->first()->uuid);

        $usedOnly = $service->paginateForCurrentSite(usage: 'used');
        $this->assertCount(1, $usedOnly);
        $this->assertSame($used->uuid, $usedOnly->first()->uuid);

        $byName = $service->paginateForCurrentSite(sort: 'name');
        $this->assertSame($used->uuid, $byName->first()->uuid, 'a-used.jpg sorts before z-unused.jpg');

        // A single withCount() aggregate query, not one usage query per row.
        DB::enableQueryLog();
        $service->paginateForCurrentSite();
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertLessThanOrEqual(2, $queryCount, 'Listing must not run one query per media row.');
    }

    public function test_replace_preserves_uuid_and_swaps_the_resolved_binary(): void
    {
        $service = app(CustomerMediaService::class);
        $media = $service->upload(UploadedFile::fake()->image('original.jpg', 100, 100));
        $originalUuid = $media->uuid;
        $originalKey = $media->storage_key;

        $replaced = $service->replace($media, UploadedFile::fake()->image('new.png', 300, 200));

        $this->assertSame($originalUuid, $replaced->uuid);
        $this->assertSame($media->id, $replaced->id);
        $this->assertSame(300, $replaced->width);
        $this->assertSame(200, $replaced->height);
        $this->assertNotSame($originalKey, $replaced->storage_key);
        Storage::disk('uploads')->assertMissing($originalKey);
        Storage::disk('uploads')->assertExists($replaced->storage_key);
        // Any existing consumer resolving through the same uuid must now see the new binary.
        $this->assertSame($replaced->storage_key, $service->findForCurrentSite($originalUuid)->storage_key);
    }

    public function test_replace_with_the_same_extension_still_changes_the_storage_key_for_cache_busting(): void
    {
        $service = app(CustomerMediaService::class);
        $media = $service->upload(UploadedFile::fake()->image('same-ext.png'));
        $originalKey = $media->storage_key;

        $replaced = $service->replace($media, UploadedFile::fake()->image('same-ext-again.png'));

        $this->assertNotSame($originalKey, $replaced->storage_key, 'A same-extension replace must still bust any cache holding the old URL.');
    }

    public function test_replace_with_incompatible_family_is_rejected_and_original_is_untouched(): void
    {
        $service = app(CustomerMediaService::class);
        $media = $service->upload(UploadedFile::fake()->image('image.jpg'));
        $originalKey = $media->storage_key;

        $this->expectException(ValidationException::class);
        try {
            $service->replace($media, UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'));
        } finally {
            $this->assertSame($originalKey, $media->fresh()->storage_key);
            Storage::disk('uploads')->assertExists($originalKey);
        }
    }

    public function test_image_rotate_and_resize_preserve_identity_and_update_dimensions(): void
    {
        $service = app(CustomerMediaService::class);
        $media = $service->upload(UploadedFile::fake()->image('edit.jpg', 200, 100));
        $originalUuid = $media->uuid;
        $originalKey = $media->storage_key;

        $rotated = $service->editImage($media, 'rotate', ['degrees' => 90]);
        $this->assertSame($originalUuid, $rotated->uuid);
        // The storage key (and therefore the public URL) DOES change on
        // every edit, same reasoning as replace(): a cache holding the old
        // URL must not keep serving the pre-edit image. Canonical identity
        // is the uuid, not the storage key.
        $this->assertNotSame($originalKey, $rotated->storage_key);
        Storage::disk('uploads')->assertMissing($originalKey);
        Storage::disk('uploads')->assertExists($rotated->storage_key);
        $this->assertSame(100, $rotated->width);
        $this->assertSame(200, $rotated->height);

        // rotated is 100x200 (portrait); constrained to fit within 50x50 the
        // HEIGHT is the binding dimension (ratio 0.25), giving 25x50.
        $resized = $service->editImage($rotated->fresh(), 'resize', ['max_width' => 50, 'max_height' => 50]);
        $this->assertSame(25, $resized->width);
        $this->assertSame(50, $resized->height);
    }

    public function test_image_crop_out_of_bounds_is_rejected(): void
    {
        $service = app(CustomerMediaService::class);
        $media = $service->upload(UploadedFile::fake()->image('crop.jpg', 100, 100));

        $this->expectException(ValidationException::class);
        $service->editImage($media, 'crop', ['x' => 50, 'y' => 50, 'width' => 100, 'height' => 100]);
    }

    public function test_image_editing_is_rejected_for_non_image_categories(): void
    {
        $service = app(CustomerMediaService::class);
        $media = $service->upload(UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'));

        $this->expectException(ValidationException::class);
        $service->editImage($media, 'rotate', ['degrees' => 90]);
    }
}
