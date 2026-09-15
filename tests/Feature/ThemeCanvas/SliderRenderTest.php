<?php

namespace Tests\Feature\ThemeCanvas;

use App\Services\CustomerMediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Builder integrity fix (2026-09-12): the `slider` element rendered every
 * slide as a `[invalid] text` placeholder on public pages — renderSlider()
 * fed plain {image,title,subtitle,link} data records into renderBlock(),
 * which requires a `type` key slides never had. This proves the actual
 * content now reaches the public render, that slide images resolve through
 * the canonical CustomerMediaResolver path, and that no invalid-schema
 * placeholder marker is ever produced by this element.
 */
class SliderRenderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true, 'site.platform_site_id' => 501]);
        Storage::fake('uploads');
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    private function renderer(): \App\Services\ThemeCanvas\ThemeCanvasRenderer
    {
        $renderer = app(\App\Services\ThemeCanvas\ThemeCanvasRenderer::class);
        $renderer->setThemeData(['pages' => []]);
        return $renderer;
    }

    public function test_slider_renders_real_slide_content_with_no_invalid_placeholder(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'sl1',
            'type' => 'slider',
            'props' => [
                'autoplay' => true,
                'interval' => 4000,
                'slides' => [
                    ['image' => '', 'title' => 'اسلاید اول', 'subtitle' => 'زیرعنوان اول', 'link' => ''],
                    ['image' => '', 'title' => 'اسلاید دوم', 'subtitle' => 'زیرعنوان دوم', 'link' => 'https://example.com/landing'],
                ],
            ],
        ]);

        $this->assertStringContainsString('اسلاید اول', $html);
        $this->assertStringContainsString('زیرعنوان اول', $html);
        $this->assertStringContainsString('اسلاید دوم', $html);
        $this->assertStringContainsString('https://example.com/landing', $html);
        $this->assertStringNotContainsString('tb-placeholder-invalid', $html);
        $this->assertStringNotContainsString('[invalid]', $html);
        $this->assertStringNotContainsString('invalid_schema', $html);
    }

    public function test_slider_image_resolves_through_canonical_media_id(): void
    {
        $media = app(CustomerMediaService::class)->upload(UploadedFile::fake()->image('slide.jpg'));
        $url = app(CustomerMediaService::class)->publicUrl($media);

        $html = $this->renderer()->renderBlock([
            'id' => 'sl2',
            'type' => 'slider',
            'props' => [
                'slides' => [
                    ['image' => ['media_id' => $media->uuid], 'title' => 'با تصویر', 'subtitle' => '', 'link' => ''],
                ],
            ],
        ]);

        $this->assertStringContainsString('src="' . $url . '"', $html);
        $this->assertStringNotContainsString('tb-placeholder-invalid', $html);
    }

    public function test_slider_with_foreign_media_id_fails_safe_to_no_image_not_a_crash(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'sl3',
            'type' => 'slider',
            'props' => [
                'slides' => [
                    ['image' => ['media_id' => (string) \Illuminate\Support\Str::uuid()], 'title' => 'گمشده', 'subtitle' => '', 'link' => ''],
                ],
            ],
        ]);

        $this->assertStringContainsString('گمشده', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('tb-placeholder-invalid', $html);
    }

    public function test_slider_with_no_slides_renders_nothing_not_a_crash(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'sl4',
            'type' => 'slider',
            'props' => ['slides' => []],
        ]);

        $this->assertSame('', trim($html));
    }

    public function test_single_slide_skips_carousel_chrome(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'sl5',
            'type' => 'slider',
            'props' => ['slides' => [
                ['image' => '', 'title' => 'تنها اسلاید', 'subtitle' => '', 'link' => ''],
            ]],
        ]);

        $this->assertStringContainsString('tb-carousel-single', $html);
        $this->assertStringNotContainsString('tb-carousel-dots', $html);
    }

    public function test_multiple_slides_render_carousel_chrome_with_autoplay_and_interval(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'sl6',
            'type' => 'slider',
            'props' => [
                'autoplay' => true,
                'interval' => 7000,
                'slides' => [
                    ['image' => '', 'title' => 'یک', 'subtitle' => '', 'link' => ''],
                    ['image' => '', 'title' => 'دو', 'subtitle' => '', 'link' => ''],
                    ['image' => '', 'title' => 'سه', 'subtitle' => '', 'link' => ''],
                ],
            ],
        ]);

        $this->assertStringContainsString('data-autoplay="true"', $html);
        $this->assertStringContainsString('data-interval="7000"', $html);
        $this->assertSame(3, substr_count($html, 'class="tb-carousel-slide"'));
        $this->assertSame(3, substr_count($html, 'class="tb-carousel-dot"'));
    }
}
