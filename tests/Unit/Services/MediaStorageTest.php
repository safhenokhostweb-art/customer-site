<?php

namespace Tests\Unit\Services;

use App\Services\MediaStorage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaStorageTest extends TestCase
{
    public function test_local_mode_preserves_legacy_disk_names(): void
    {
        Config::set('filesystems.upload_disk', 'uploads');
        Config::set('filesystems.media_private_disk', 'private');

        $media = app(MediaStorage::class);

        $this->assertSame('uploads', $media->diskNameFor('uploads'));
        $this->assertSame('public', $media->diskNameFor('legacy'));
        $this->assertSame('private', $media->diskNameFor('private'));
    }

    public function test_media_disk_env_toggle_unifies_uploads_and_legacy_kinds(): void
    {
        // Simulates MEDIA_DISK=arvan without touching real env/files.
        Config::set('filesystems.upload_disk', 'arvan');

        $media = app(MediaStorage::class);

        $this->assertSame('arvan', $media->diskNameFor('uploads'));
        $this->assertSame('arvan', $media->diskNameFor('legacy'));
    }

    public function test_private_disk_switches_independently_of_public_media_disk(): void
    {
        Config::set('filesystems.upload_disk', 'arvan');
        Config::set('filesystems.media_private_disk', 'arvan_private');

        $media = app(MediaStorage::class);

        $this->assertSame('arvan_private', $media->diskNameFor('private'));
    }

    public function test_storage_local_upload_and_delete_round_trip(): void
    {
        Storage::fake('uploads');
        Config::set('filesystems.upload_disk', 'uploads');

        $media = app(MediaStorage::class);
        $media->disk('uploads')->put('thumbnail/2026/08/x.webp', 'binary-data');

        Storage::disk('uploads')->assertExists('thumbnail/2026/08/x.webp');

        $media->delete('thumbnail/2026/08/x.webp', 'uploads');

        Storage::disk('uploads')->assertMissing('thumbnail/2026/08/x.webp');
    }

    public function test_url_generation_follows_the_resolved_disk(): void
    {
        Storage::fake('uploads');
        Config::set('filesystems.upload_disk', 'uploads');

        $media = app(MediaStorage::class);
        Storage::disk('uploads')->put('thumbnail/x.webp', 'binary-data');

        $url = $media->url('thumbnail/x.webp', 'uploads');

        $this->assertStringEndsWith('thumbnail/x.webp', $url);
    }

    public function test_legacy_kind_resolves_through_the_public_disk(): void
    {
        Storage::fake('public');
        Config::set('filesystems.upload_disk', 'uploads');

        $media = app(MediaStorage::class);
        Storage::disk('public')->put('uploads/legacy.jpg', 'binary-data');

        $url = $media->url('uploads/legacy.jpg', 'legacy');

        $this->assertStringEndsWith('uploads/legacy.jpg', $url);
    }

    /**
     * Reproduces the production incident: the 'uploads' disk root
     * (public_path('uploads')) is missing and its parent can't be written
     * to, so League\Flysystem\Local\LocalFilesystemAdapter's constructor
     * throws UnableToCreateDirectory the moment Storage::disk('uploads') is
     * resolved — before any read/write operation runs. url() must build the
     * link straight from config instead of resolving the adapter, so a
     * broken root never touches the filesystem and never throws.
     */
    private function configureBrokenLocalDisk(string $name, ?string $url): string
    {
        $blockingFile = sys_get_temp_dir().'/media_storage_test_blocker_'.uniqid('', true);
        file_put_contents($blockingFile, 'not-a-directory');

        $config = [
            'driver' => 'local',
            'root' => $blockingFile,
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ];
        if ($url !== null) {
            $config['url'] = $url;
        }

        Config::set("filesystems.disks.{$name}", $config);
        Config::set('filesystems.upload_disk', $name);

        return $blockingFile;
    }

    public function test_url_never_touches_the_filesystem_for_a_local_disk_with_a_configured_url(): void
    {
        $blockingFile = $this->configureBrokenLocalDisk('broken_uploads', 'http://example.test/uploads');

        $media = app(MediaStorage::class);
        $url = $media->url('thumbnail/2026/08/x.webp', 'uploads');

        $this->assertSame('http://example.test/uploads/thumbnail/2026/08/x.webp', $url);
        // The blocking path is still a plain file — proves mkdir was never attempted.
        $this->assertTrue(is_file($blockingFile));

        @unlink($blockingFile);
    }

    public function test_url_fails_soft_to_empty_string_when_local_disk_has_no_configured_url_and_root_is_unusable(): void
    {
        $blockingFile = $this->configureBrokenLocalDisk('broken_uploads', null);

        $media = app(MediaStorage::class);
        $url = $media->url('thumbnail/2026/08/x.webp', 'uploads');

        $this->assertSame('', $url);

        @unlink($blockingFile);
    }

    public function test_exists_fails_soft_to_false_when_disk_root_is_unusable(): void
    {
        $blockingFile = $this->configureBrokenLocalDisk('broken_uploads', null);

        $media = app(MediaStorage::class);

        $this->assertFalse($media->exists('thumbnail/2026/08/x.webp', 'uploads'));

        @unlink($blockingFile);
    }

    public function test_delete_fails_soft_to_false_when_disk_root_is_unusable(): void
    {
        $blockingFile = $this->configureBrokenLocalDisk('broken_uploads', null);

        $media = app(MediaStorage::class);

        $this->assertFalse($media->delete('thumbnail/2026/08/x.webp', 'uploads'));

        @unlink($blockingFile);
    }

    /**
     * Pins the persistent-uploads contract documented in
     * docs/deployment/CUSTOMER_SITE_RUNTIME_CONTRACT.md: the 'uploads' disk
     * root must stay public_path('uploads') so deployment-system knows
     * exactly which path to preserve/symlink across release swaps.
     */
    public function test_uploads_disk_root_is_the_documented_persistent_path(): void
    {
        $this->assertSame(public_path('uploads'), config('filesystems.disks.uploads.root'));
    }
}
