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
}
