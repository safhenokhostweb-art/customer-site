<?php

namespace Tests\Feature\Components;

use App\Models\HomepageVideo;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * <x-video-player> must never turn a missing/unresolvable thumbnail into a
 * fatal error: MediaStorage::url() degrading to a safe value (see
 * MediaStorageTest / ImageHelperTest) means ImageHelper::url() returns null
 * for the thumbnail, and the component must render the rest of the player
 * normally around that.
 */
class VideoPlayerThumbnailFailureTest extends TestCase
{
    private ?string $blockingFile = null;

    protected function tearDown(): void
    {
        if ($this->blockingFile !== null) {
            @unlink($this->blockingFile);
        }
        parent::tearDown();
    }

    public function test_component_renders_the_video_without_a_thumbnail_when_storage_is_unusable(): void
    {
        $this->blockingFile = sys_get_temp_dir().'/video_player_test_blocker_'.uniqid('', true);
        file_put_contents($this->blockingFile, 'not-a-directory');

        Config::set('filesystems.disks.uploads', [
            'driver' => 'local',
            'root' => $this->blockingFile,
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ]);

        $video = new HomepageVideo([
            'title' => 'Test Video',
            'video_type' => 'mp4',
            'video_url' => 'https://cdn.example.com/video.mp4',
            'thumbnail_url' => 'video_thumb/2026/08/thumb.webp',
            'autoplay' => false,
            'loop' => false,
            'muted' => false,
            'overlay_opacity' => 30,
        ]);

        $html = Blade::render('<x-video-player :video="$video" />', ['video' => $video]);

        $this->assertStringContainsString('<video', $html);
        $this->assertStringContainsString('cdn.example.com/video.mp4', $html);
        $this->assertStringNotContainsString('background-image:url(\'', $html);
    }
}
