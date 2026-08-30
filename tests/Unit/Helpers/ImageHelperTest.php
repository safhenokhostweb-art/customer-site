<?php

namespace Tests\Unit\Helpers;

use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageHelperTest extends TestCase
{
    public function test_null_and_empty_paths_return_null(): void
    {
        $this->assertNull(ImageHelper::url(null));
        $this->assertNull(ImageHelper::url(''));
        $this->assertNull(ImageHelper::url('   '));
    }

    public function test_absolute_urls_pass_through_unchanged(): void
    {
        $this->assertSame('https://cdn.example.com/x.jpg', ImageHelper::url('https://cdn.example.com/x.jpg'));
    }

    public function test_new_style_paths_resolve_through_the_uploads_disk(): void
    {
        Storage::fake('uploads');
        Config::set('filesystems.upload_disk', 'uploads');

        Storage::disk('uploads')->put('thumbnail/2026/08/x.webp', 'binary-data');

        $url = ImageHelper::url('thumbnail/2026/08/x.webp');

        $this->assertStringEndsWith('thumbnail/2026/08/x.webp', $url);
    }

    public function test_legacy_style_paths_resolve_through_the_public_disk(): void
    {
        Storage::fake('public');
        Config::set('filesystems.upload_disk', 'uploads');

        Storage::disk('public')->put('uploads/legacy.jpg', 'binary-data');

        $url = ImageHelper::url('uploads/legacy.jpg');

        $this->assertStringEndsWith('uploads/legacy.jpg', $url);
    }

    /**
     * Production incident: public/uploads (the local 'uploads' disk root)
     * is missing and its parent isn't writable. Resolving the disk used to
     * throw League\Flysystem\UnableToCreateDirectory from inside a plain
     * page render (video-player.blade.php -> ImageHelper::url()). This must
     * degrade to null instead of throwing, so the page still renders.
     */
    public function test_unusable_storage_root_degrades_to_null_instead_of_throwing(): void
    {
        $blockingFile = sys_get_temp_dir().'/image_helper_test_blocker_'.uniqid('', true);
        file_put_contents($blockingFile, 'not-a-directory');

        Config::set('filesystems.disks.broken_uploads', [
            'driver' => 'local',
            'root' => $blockingFile,
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ]);
        Config::set('filesystems.upload_disk', 'broken_uploads');

        $this->assertNull(ImageHelper::url('thumbnail/2026/08/x.webp'));

        @unlink($blockingFile);
    }
}
