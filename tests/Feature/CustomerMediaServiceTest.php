<?php

namespace Tests\Feature;

use App\Models\CustomerMedia;
use App\Services\CustomerMediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
        Storage::fake('uploads');
        config(['site.platform_site_id' => 101]);
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
}
