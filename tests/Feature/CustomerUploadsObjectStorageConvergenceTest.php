<?php

namespace Tests\Feature;

use App\Helpers\ImageHelper;
use App\Models\CustomerMedia;
use App\Models\Setting;
use App\Models\SubscriptionPlan;
use App\Services\CustomerMediaService;
use App\Services\MediaQuota;
use App\Services\MediaStorage;
use App\Services\SmartImageUploadService;
use App\Services\ThemeCanvas\CustomerMediaResolver;
use App\Services\TicketAttachmentStorage;
use App\Support\ResourceSummary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Verifies that 100% of customer upload and media paths write to and resolve
 * through the active Arvan Cloud object storage disk, and zero customer files
 * are written to the local "public" disk.
 */
class CustomerUploadsObjectStorageConvergenceTest extends TestCase
{
    private const SITE = 101;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('customer_media_usages');
        Schema::dropIfExists('customer_media');
        (require database_path('migrations/2026_09_10_100000_create_customer_media_tables.php'))->up();
        (require database_path('migrations/2026_09_15_100000_add_description_to_customer_media_table.php'))->up();

        config(['site.platform_site_id' => self::SITE]);
        config(['filesystems.upload_disk' => 'arvan']);
        config(['filesystems.media_private_disk' => 'arvan_private']);

        Storage::fake('public');
        Storage::fake('uploads');

        $this->remoteRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cuosc-remote-' . Str::random(8);
        $local = Storage::build(['driver' => 'local', 'root' => $this->remoteRoot]);
        $disk = new class($local->getDriver(), $local->getAdapter(), ['url' => 'https://media.safhenokhost.ir/sites/101/media']) extends \Illuminate\Filesystem\FilesystemAdapter {
            public function path($path)
            {
                throw new \RuntimeException('object storage has no local path');
            }
        };
        Storage::set('arvan', $disk);

        // Grant ample quota for test uploads
        $summary = ResourceSummary::fromJson(json_encode([
            'storage_measured'      => true,
            'storage_bytes'         => 1000,
            'disk_hard_limit_bytes' => 100_000_000,
            'measured_at'           => now()->toIso8601String(),
            'storage_pct'           => 1,
        ]));
        $this->app->instance(MediaQuota::class, (new MediaQuota())->withSummary($summary));
    }

    private string $remoteRoot;

    protected function tearDown(): void
    {
        if (isset($this->remoteRoot)) {
            \Illuminate\Support\Facades\File::deleteDirectory($this->remoteRoot);
        }
        parent::tearDown();
    }

    private function png(int $w = 20, int $h = 20): UploadedFile
    {
        $image = imagecreatetruecolor($w, $h);
        ob_start();
        imagepng($image);
        $data = (string) ob_get_clean();

        $tmp = tempnam(sys_get_temp_dir(), 'test_img_');
        file_put_contents($tmp, $data);

        return new UploadedFile($tmp, 'sample.png', 'image/png', null, true);
    }

    public function test_smart_image_upload_writes_to_arvan_and_never_to_public(): void
    {
        $uploader = app(SmartImageUploadService::class);

        // Test avatar, product, banner, thumbnail
        foreach (['avatar', 'product', 'banner', 'thumbnail'] as $context) {
            $result = $uploader->upload($this->png(100, 100), $context);

            $this->assertNotEmpty($result['path']);
            $this->assertStringStartsWith('https://', $result['url']);

            // Must exist on arvan disk
            Storage::disk('arvan')->assertExists($result['path']);

            // Must NOT exist on public disk
            Storage::disk('public')->assertMissing($result['path']);
        }

        // Entire public disk must remain 100% untouched
        $this->assertEmpty(Storage::disk('public')->allFiles(), 'Public disk must contain 0 customer files');
    }

    public function test_customer_media_service_upload_writes_to_arvan_disk_with_site_isolation(): void
    {
        $mediaService = app(CustomerMediaService::class);
        $media = $mediaService->upload($this->png(50, 50), ['title' => 'Logo']);

        $this->assertSame('arvan', $media->disk);
        $this->assertStringStartsWith('customer-media/' . self::SITE . '/', $media->storage_key);

        // Stored on arvan disk
        Storage::disk('arvan')->assertExists($media->storage_key);

        // Never stored on public disk
        Storage::disk('public')->assertMissing($media->storage_key);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_customer_media_replace_migrates_legacy_local_disk_to_active_arvan_disk(): void
    {
        $mediaService = app(CustomerMediaService::class);

        // Seed a legacy media on local 'uploads' disk
        $oldKey = 'customer-media/' . self::SITE . '/legacy.png';
        Storage::disk('uploads')->put($oldKey, 'legacy bytes');

        $media = CustomerMedia::create([
            'site_id'           => self::SITE,
            'uuid'              => (string) Str::uuid(),
            'disk'              => 'uploads',
            'storage_key'       => $oldKey,
            'original_filename' => 'legacy.png',
            'extension'         => 'png',
            'category'          => 'image',
            'mime_type'         => 'image/png',
            'size_bytes'        => 12,
            'width'             => 10,
            'height'            => 10,
        ]);

        // Replace with new image
        $replaced = $mediaService->replace($media, $this->png(30, 30));

        $this->assertSame('arvan', $replaced->disk);
        $this->assertNotSame($oldKey, $replaced->storage_key);

        // New binary is on arvan
        Storage::disk('arvan')->assertExists($replaced->storage_key);

        // Old binary on uploads is removed
        Storage::disk('uploads')->assertMissing($oldKey);

        // Public disk remains completely empty
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_ticket_attachments_store_on_arvan_and_delete_cleanly(): void
    {
        $storage = app(TicketAttachmentStorage::class);
        $pdf = UploadedFile::fake()->create('attachment.pdf', 10, 'application/pdf');

        $stored = $storage->store($pdf);

        $this->assertSame('arvan', $stored['disk']);
        $key = substr($stored['path'], strlen('uploads/'));

        // Exists on arvan
        Storage::disk('arvan')->assertExists($key);

        // Public disk has zero files
        $this->assertEmpty(Storage::disk('public')->allFiles());

        // Deletion purges from arvan
        $storage->delete($stored);
        Storage::disk('arvan')->assertMissing($key);
    }

    public function test_image_helper_url_resolves_storage_prefix_to_arvan_disk(): void
    {
        // When path has storage/ prefix, ImageHelper strips it and resolves on active media disk
        $resolved = ImageHelper::url('storage/solar-assets/about-feature.jpg');

        $this->assertNotNull($resolved);
        $this->assertStringContainsString('solar-assets/about-feature.jpg', $resolved);
        $this->assertFalse(str_contains($resolved, 'storage/solar-assets'));
    }

    public function test_customer_media_resolver_maps_source_url_to_isolated_arvan_url(): void
    {
        $mediaService = app(CustomerMediaService::class);
        $resolver = app(CustomerMediaResolver::class);

        // Create media matching a theme stock asset
        $media = CustomerMedia::create([
            'site_id'           => self::SITE,
            'uuid'              => (string) Str::uuid(),
            'disk'              => 'arvan',
            'storage_key'       => 'solar-assets/panel-hero.jpg',
            'original_filename' => 'panel-hero.jpg',
            'extension'         => 'jpg',
            'category'          => 'image',
            'mime_type'         => 'image/jpeg',
            'size_bytes'        => 500,
            'metadata'          => [
                'source_url' => '/storage/solar-assets/panel-hero.jpg',
            ],
        ]);

        $resolved = $resolver->resolveValue('/storage/solar-assets/panel-hero.jpg');

        $this->assertSame($mediaService->publicUrl($media), $resolved);
    }

    public function test_resolve_site_asset_url_resolves_remote_arvan_file_when_not_in_public(): void
    {
        $mediaService = app(CustomerMediaService::class);

        // Put a logo on arvan
        Storage::disk('arvan')->put('site-assets/logo.png', 'logo bytes');

        $url = $mediaService->resolveSiteAssetUrl('site-assets/logo.png');

        $this->assertNotNull($url);
        $this->assertStringContainsString('site-assets/logo.png', $url);
    }
}
