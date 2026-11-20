<?php

namespace Tests\Feature;

use App\Models\CustomerMedia;
use App\Models\SubscriptionPlan;
use App\Services\CustomerMediaService;
use App\Services\MediaQuota;
use App\Services\TicketAttachmentStorage;
use App\Support\ResourceSummary;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Phase 4 — customer media on object storage: no local-path dependency,
 * upload-time quota, ticket/plan files through MediaStorage, and the
 * verified media:migrate-to-remote move.
 */
class TenantMediaStorageTest extends TestCase
{
    private const SITE = 101;

    private string $remoteRoot;

    protected function setUp(): void
    {
        parent::setUp();
        // Same focused schema setup as CustomerMediaServiceTest.
        Schema::dropIfExists('customer_media_usages');
        Schema::dropIfExists('customer_media');
        (require database_path('migrations/2026_09_10_100000_create_customer_media_tables.php'))->up();
        (require database_path('migrations/2026_09_15_100000_add_description_to_customer_media_table.php'))->up();
        config(['site.platform_site_id' => self::SITE]);
        Storage::fake('uploads');
        $this->remoteRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tms-remote-' . Str::random(8);
    }

    protected function tearDown(): void
    {
        \Illuminate\Support\Facades\File::deleteDirectory($this->remoteRoot);
        parent::tearDown();
    }

    /** An object-storage stand-in: works like a bucket, has no local path(). */
    private function remoteDisk(bool $corruptWrites = false): FilesystemAdapter
    {
        $local = Storage::build(['driver' => 'local', 'root' => $this->remoteRoot]);
        $disk = new class($local->getDriver(), $local->getAdapter(), ['url' => 'https://media.example/sites/101/media'], $corruptWrites) extends FilesystemAdapter {
            public function __construct($driver, $adapter, array $config, private readonly bool $corrupt)
            {
                parent::__construct($driver, $adapter, $config);
            }

            public function path($path)
            {
                throw new \RuntimeException('object storage has no local path');
            }

            public function put($path, $contents, $options = [])
            {
                return parent::put($path, $this->corrupt ? 'corrupted-bytes' : $contents, $options);
            }
        };
        Storage::set('arvan', $disk);
        config(['filesystems.upload_disk' => 'arvan']);

        return $disk;
    }

    private function png(int $w, int $h): string
    {
        $image = imagecreatetruecolor($w, $h);
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    private function media(string $disk, string $key, string $bytes, array $extra = []): CustomerMedia
    {
        Storage::disk($disk)->put($key, $bytes);

        return CustomerMedia::create(array_merge([
            'site_id' => self::SITE, 'uuid' => (string) Str::uuid(), 'disk' => $disk, 'storage_key' => $key,
            'original_filename' => 'a.png', 'extension' => 'png', 'category' => 'image', 'mime_type' => 'image/png',
            'size_bytes' => strlen($bytes), 'width' => 20, 'height' => 10,
        ], $extra));
    }

    private function quota(int $used, int $limit, bool $measured = true, ?string $measuredAt = null): void
    {
        $summary = ResourceSummary::fromJson(json_encode([
            'storage_measured' => $measured, 'storage_bytes' => $used, 'disk_hard_limit_bytes' => $limit,
            'measured_at' => $measuredAt ?? now()->toIso8601String(), 'storage_pct' => 1,
        ]));
        $this->app->instance(MediaQuota::class, (new MediaQuota())->withSummary($summary));
    }

    // ── editImage ────────────────────────────────────────────────────────

    public function test_edit_image_works_on_object_storage_and_leaves_no_temp_file(): void
    {
        $remote = $this->remoteDisk();
        $media = $this->media('arvan', 'customer-media/101/2026/11/a.png', $this->png(20, 10));
        $tempBefore = glob(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cm-edit-*') ?: [];

        $edited = app(CustomerMediaService::class)->editImage($media, 'rotate', ['degrees' => 90]);

        $this->assertSame([10, 20], [$edited->width, $edited->height]);
        $this->assertNotSame('customer-media/101/2026/11/a.png', $edited->storage_key);
        $this->assertTrue($remote->exists($edited->storage_key));
        $this->assertFalse($remote->exists('customer-media/101/2026/11/a.png'));
        $this->assertSame($tempBefore, glob(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cm-edit-*') ?: []);
    }

    // ── quota ────────────────────────────────────────────────────────────

    public function test_upload_over_the_platform_hard_limit_is_rejected(): void
    {
        $file = UploadedFile::fake()->image('a.png', 10, 10);
        $this->quota(1000, 1000 + $file->getSize() - 1);

        try {
            app(CustomerMediaService::class)->upload($file);
            $this->fail('upload over the limit must be refused');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('فضای ذخیره‌سازی سایت پر است', $e->errors()['file'][0]);
        }
        $this->assertSame(0, CustomerMedia::query()->count());
    }

    public function test_upload_within_the_limit_or_without_a_measurement_is_allowed(): void
    {
        $this->quota(1000, 10_000_000);
        app(CustomerMediaService::class)->upload(UploadedFile::fake()->image('a.png', 10, 10));

        $this->quota(999_999_999, 1, measured: false);
        app(CustomerMediaService::class)->upload(UploadedFile::fake()->image('b.png', 10, 10));

        $this->assertSame(2, CustomerMedia::query()->count());
    }

    public function test_media_stored_since_the_last_measurement_counts_against_the_limit(): void
    {
        $this->media('uploads', 'customer-media/101/x.bin', str_repeat('x', 500));
        $quota = (new MediaQuota())->withSummary(ResourceSummary::fromJson(json_encode([
            'storage_measured' => true, 'storage_bytes' => 400, 'disk_hard_limit_bytes' => 1000,
            'measured_at' => now()->subHour()->toIso8601String(),
        ])));

        $quota->assertCanStore(100); // 400 + 500 + 100 = 1000: still fits

        $this->expectException(ValidationException::class);
        $quota->assertCanStore(101);
    }

    // ── tickets, plans ───────────────────────────────────────────────────

    public function test_ticket_attachment_keeps_the_local_path_and_url_shape(): void
    {
        $this->quota(0, 10_000_000);

        $stored = app(TicketAttachmentStorage::class)->store(UploadedFile::fake()->create('doc.pdf', 3, 'application/pdf'));

        $this->assertMatchesRegularExpression('#^uploads/tickets/\d{4}/\d{2}/[A-Za-z0-9]{32}\.pdf$#', $stored['path']);
        $this->assertStringEndsWith('/' . $stored['path'], $stored['url']);
        $this->assertSame('uploads', $stored['disk']);
        Storage::disk('uploads')->assertExists(substr($stored['path'], strlen('uploads/')));
    }

    public function test_ticket_attachment_goes_to_object_storage_and_can_be_deleted(): void
    {
        $remote = $this->remoteDisk();
        $this->quota(0, 10_000_000);
        $storage = app(TicketAttachmentStorage::class);

        $stored = $storage->store(UploadedFile::fake()->create('doc.pdf', 3, 'application/pdf'));
        $key = substr($stored['path'], strlen('uploads/'));

        $this->assertSame('arvan', $stored['disk']);
        $this->assertStringStartsWith('https://media.example/sites/101/media/tickets/', $stored['url']);
        $this->assertTrue($remote->exists($key));

        $storage->delete($stored);
        $this->assertFalse($remote->exists($key));
    }

    public function test_plan_images_resolve_on_the_media_disk_and_legacy_paths_on_public(): void
    {
        $this->remoteDisk();

        $this->assertSame('https://media.example/sites/101/media/plans/x.png', (new SubscriptionPlan(['image' => 'plans/x.png']))->image_url);
        $this->assertSame(asset('storage/subscriptions/old.png'), (new SubscriptionPlan(['image' => 'subscriptions/old.png']))->image_url);
        $this->assertNull((new SubscriptionPlan(['image' => null]))->image_url);
    }

    // ── media:migrate-to-remote ──────────────────────────────────────────

    public function test_migration_dry_run_changes_nothing(): void
    {
        $this->remoteDisk();
        $media = $this->media('uploads', 'customer-media/101/a.png', $this->png(4, 4));

        $this->artisan('media:migrate-to-remote', ['--site' => self::SITE])->expectsOutputToContain('DRY RUN')->assertSuccessful();

        $this->assertSame('uploads', $media->fresh()->disk);
        Storage::disk('uploads')->assertExists('customer-media/101/a.png');
    }

    public function test_migration_moves_verifies_switches_and_deletes_the_local_copy(): void
    {
        $remote = $this->remoteDisk();
        $bytes = $this->png(4, 4);
        $media = $this->media('uploads', 'customer-media/101/a.png', $bytes);
        $kept = $this->media('uploads', 'customer-media/101/b.png', $bytes);

        $this->artisan('media:migrate-to-remote', ['--site' => self::SITE, '--execute' => true, '--limit' => 1])->assertSuccessful();
        $this->artisan('media:migrate-to-remote', ['--site' => self::SITE, '--execute' => true, '--keep-local' => true])->assertSuccessful();

        $this->assertSame('arvan', $media->fresh()->disk);
        $this->assertSame(hash('sha256', $bytes), hash('sha256', (string) $remote->get('customer-media/101/a.png')));
        Storage::disk('uploads')->assertMissing('customer-media/101/a.png');
        $this->assertSame('arvan', $kept->fresh()->disk);
        Storage::disk('uploads')->assertExists('customer-media/101/b.png');
        $this->assertStringStartsWith('https://media.example/', app(CustomerMediaService::class)->publicUrl($media->fresh()));
    }

    public function test_a_copy_that_fails_verification_keeps_the_local_file_and_row(): void
    {
        $remote = $this->remoteDisk(corruptWrites: true);
        $media = $this->media('uploads', 'customer-media/101/a.png', $this->png(4, 4));

        $this->artisan('media:migrate-to-remote', ['--site' => self::SITE, '--execute' => true])->assertFailed();

        $this->assertSame('uploads', $media->fresh()->disk);
        Storage::disk('uploads')->assertExists('customer-media/101/a.png');
        $this->assertFalse($remote->exists('customer-media/101/a.png'), 'the unverified copy is removed');
    }

    public function test_migration_refuses_the_wrong_site_or_a_local_media_disk(): void
    {
        $this->artisan('media:migrate-to-remote', ['--site' => self::SITE])->assertFailed(); // MEDIA_DISK still local

        $this->remoteDisk();
        $this->artisan('media:migrate-to-remote', ['--site' => 999])->assertFailed();
        $this->artisan('media:migrate-to-remote')->assertFailed();
    }
}
