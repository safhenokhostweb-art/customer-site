<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Advertisement;
use App\Models\License;
use App\Models\SiteNotification;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Phase 0.2c security patch: the notifications bar and the advertisement
 * zone render admin-entered URLs. Both went through bare escapeAttr(),
 * which let a javascript: link reach the public page. They now use the
 * shared escapeUrl() like every Canvas link field (ADR-061).
 */
class ModuleAdminLinkSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        License::query()->create([
            'license_key' => 'LINK-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['advertisements'],
        ]);
    }

    private function render(string $type): string
    {
        $renderer = app(ThemeCanvasRenderer::class);
        $renderer->setThemeData(['pages' => []]);
        return $renderer->renderBlock(['id' => 'ls1', 'type' => $type, 'props' => []]);
    }

    private function notification(?string $link): void
    {
        SiteNotification::create(['title' => 'اطلاعیه', 'body' => 'تخفیف ویژه', 'link' => $link, 'is_active' => true]);
    }

    private function advertisement(?string $link): void
    {
        Advertisement::create([
            'type' => 'banner', 'title' => 'تبلیغ', 'content' => 'متن تبلیغ',
            'link' => $link, 'position' => 'banner-main', 'is_active' => true,
        ]);
    }

    /** @return array<string, array{string}> */
    public static function unsafeLinks(): array
    {
        return [
            'javascript' => ['javascript:alert(1)'],
            'mixed case + padding' => ["  JaVaScRiPt:alert(1)"],
            'data' => ['data:text/html,<script>alert(1)</script>'],
            'vbscript' => ['vbscript:msgbox(1)'],
        ];
    }

    #[DataProvider('unsafeLinks')]
    public function test_notifications_bar_drops_an_unsafe_link_and_keeps_the_message(string $link): void
    {
        $this->notification($link);
        $html = $this->render('module:notifications_bar');

        $this->assertStringContainsString('تخفیف ویژه', $html);
        $this->assertStringNotContainsStringIgnoringCase('script:', $html);
        $this->assertStringNotContainsString('data:', $html);
        $this->assertStringNotContainsString('<a ', $html);
    }

    public function test_notifications_bar_keeps_a_safe_link(): void
    {
        $this->notification('/shop?sale=1&page=2');
        $html = $this->render('module:notifications_bar');

        $this->assertStringContainsString('<a href="/shop?sale=1&amp;page=2"', $html);
        $this->assertStringContainsString('تخفیف ویژه</a>', $html);
    }

    public function test_notifications_bar_without_a_link_renders_plain_text(): void
    {
        $this->notification(null);
        $html = $this->render('module:notifications_bar');

        $this->assertStringContainsString('تخفیف ویژه', $html);
        $this->assertStringNotContainsString('<a ', $html);
    }

    #[DataProvider('unsafeLinks')]
    public function test_advertisement_refuses_an_unsafe_link(string $link): void
    {
        $this->advertisement($link);
        $html = $this->render('module:advertisement_zone');

        $this->assertStringContainsString('<a href="#"', $html);
        $this->assertStringNotContainsStringIgnoringCase('script:', $html);
        $this->assertStringNotContainsString('data:', $html);
    }

    public function test_advertisement_keeps_a_safe_link(): void
    {
        $this->advertisement('https://example.com/landing?a=1&b=2');
        $html = $this->render('module:advertisement_zone');

        $this->assertStringContainsString('<a href="https://example.com/landing?a=1&amp;b=2"', $html);
    }
}
