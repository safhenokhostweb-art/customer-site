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
}
