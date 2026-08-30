<?php

namespace Tests\Feature;

use App\Models\HomepageVideo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Reproduces the production incident: /var/www/customer-site/public/uploads
 * was missing from the active release and its parent wasn't writable by
 * www-data. Rendering the homepage video thumbnail
 * (video-player.blade.php -> ImageHelper::url() -> MediaStorage::url())
 * resolved the 'uploads' disk, which made Flysystem's local adapter try to
 * mkdir its root and throw League\Flysystem\UnableToCreateDirectory,
 * turning a plain page render into a fatal 500.
 *
 * The homepage must render normally even when the uploads root can't be
 * created; a missing thumbnail is not a reason to fail the whole request.
 */
class HomepageRendersWithUnusableUploadsRootTest extends TestCase
{
    use RefreshDatabase;

    private ?string $blockingFile = null;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true]);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        if ($this->blockingFile !== null) {
            @unlink($this->blockingFile);
        }
        parent::tearDown();
    }

    public function test_homepage_returns_200_when_uploads_root_cannot_be_created(): void
    {
        // A regular file sitting where the disk root should be: mkdir() on
        // it fails the same way a permission-denied parent directory would,
        // without touching real filesystem permissions.
        $this->blockingFile = sys_get_temp_dir().'/homepage_uploads_blocker_'.uniqid('', true);
        file_put_contents($this->blockingFile, 'not-a-directory');

        Config::set('filesystems.disks.uploads', [
            'driver' => 'local',
            'root' => $this->blockingFile,
            'url' => rtrim(config('app.url'), '/').'/uploads',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ]);

        HomepageVideo::create([
            'title' => 'Test Video',
            'video_type' => 'mp4',
            'video_url' => 'https://cdn.example.com/video.mp4',
            'thumbnail_url' => 'video_thumb/2026/08/thumb.webp',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
