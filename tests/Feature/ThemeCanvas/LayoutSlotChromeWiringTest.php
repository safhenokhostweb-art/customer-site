<?php

namespace Tests\Feature\ThemeCanvas;

use App\Services\ThemeCanvas\LayoutSlotBindingService;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Live-page wiring proof (Phase 11 follow-up): the flag-gated middleware
 * annotates the canvas-theme chrome <header>/<footer> tags with the
 * data-layout-slot markers — content-preserving, idempotent, and a no-op
 * everywhere else.
 */
class LayoutSlotChromeWiringTest extends TestCase
{
    private function chromePage(): string
    {
        return <<<'HTML'
<!doctype html><html lang="fa" dir="rtl"><body>
<script>if (window.ThemeCanvasRuntime?.version === 1) return;</script>
<header class="canvas-site-header tb-site-header" role="banner" id="site-header"><div class="tb-header-inner">HEADER CONTENT</div></header>
<main>CONTENT STREAM</main>
<footer class="canvas-site-footer tb-site-footer" role="contentinfo" id="site-footer"><div>FOOTER CONTENT</div></footer>
</body></html>
HTML;
    }

    public function test_decorate_chrome_adds_markers_to_canvas_chrome_tags_only(): void
    {
        $decorated = app(LayoutSlotBindingService::class)->decorateChrome($this->chromePage());

        // Marker lands on the canvas chrome tag (appended to its attribute list).
        self::assertMatchesRegularExpression('/<header\b[^>]*class="[^"]*canvas-site-header[^"]*"[^>]*data-layout-slot="header"[^>]*>/', $decorated);
        self::assertMatchesRegularExpression('/<footer\b[^>]*class="[^"]*canvas-site-footer[^"]*"[^>]*data-layout-slot="footer"[^>]*>/', $decorated);

        // Content preserved byte-for-byte apart from the markers.
        self::assertStringContainsString('HEADER CONTENT', $decorated);
        self::assertStringContainsString('FOOTER CONTENT', $decorated);
        self::assertStringContainsString('CONTENT STREAM', $decorated);
    }

    public function test_decorate_chrome_is_idempotent(): void
    {
        $service = app(LayoutSlotBindingService::class);
        $once = $service->decorateChrome($this->chromePage());
        $twice = $service->decorateChrome($once);

        self::assertSame($once, $twice);
        self::assertSame(2, substr_count($twice, 'data-layout-slot='));
    }

    public function test_decorate_chrome_annotates_theme_chrome_root_blocks(): void
    {
        // How active themes actually emit chrome (verified live — FoodMart):
        // the <header>/<footer> ARE block roots with data-tb-block-type.
        $html = <<<'HTML'
<script>if (window.ThemeCanvasRuntime?.version === 1) return;</script>
<header data-tb-block-id="foodmart_header" data-tb-block-type="foodmart_header"><nav>BRAND</nav></header>
<main>CONTENT</main>
<footer data-tb-block-id="foodmart_footer" data-tb-block-type="foodmart_footer" class="py-5"><div>FOOTER BITS</div></footer>
HTML;

        $decorated = app(LayoutSlotBindingService::class)->decorateChrome($html);

        self::assertStringContainsString('data-tb-block-type="foodmart_header" data-layout-slot="header">', $decorated);
        self::assertStringContainsString('class="py-5" data-layout-slot="footer">', $decorated);
        self::assertStringContainsString('BRAND', $decorated);
        self::assertStringContainsString('FOOTER BITS', $decorated);
    }

    public function test_decorate_chrome_leaves_non_chrome_html_untouched(): void
    {
        $html = '<html><body><header><div>plain</div></header><p>no runtime bootstrap here</p></body></html>';

        self::assertSame($html, app(LayoutSlotBindingService::class)->decorateChrome($html));
    }

    public function test_middleware_annotates_canvas_response_when_flag_enabled(): void
    {
        config()->set('canvas.slot_binding_enabled', true);
        $this->registerProbeRoute();

        $response = $this->get('/__slot_probe');
        $body = (string) $response->getContent();

        self::assertTrue($response->isSuccessful());
        self::assertStringContainsString('data-layout-slot="header"', $body);
        self::assertStringContainsString('data-layout-slot="footer"', $body);
        self::assertStringContainsString('HEADER CONTENT', $body);
    }

    public function test_middleware_is_a_no_op_when_flag_disabled(): void
    {
        config()->set('canvas.slot_binding_enabled', false);
        $this->registerProbeRoute();

        $response = $this->get('/__slot_probe');
        $body = (string) $response->getContent();

        self::assertTrue($response->isSuccessful());
        self::assertStringNotContainsString('data-layout-slot=', $body);
        self::assertStringContainsString('HEADER CONTENT', $body);
    }

    public function test_middleware_leaves_non_canvas_html_untouched(): void
    {
        config()->set('canvas.slot_binding_enabled', true);

        Route::get('/__plain_probe', fn () => response(
            '<html><body><header><div>PLAIN</div></header></body></html>',
            200,
            ['Content-Type' => 'text/html'],
        ))->middleware('web');

        $body = (string) $this->get('/__plain_probe')->getContent();

        self::assertStringNotContainsString('data-layout-slot=', $body);
    }

    private function registerProbeRoute(): void
    {
        Route::get('/__slot_probe', fn () => response($this->chromePage(), 200, ['Content-Type' => 'text/html']))
            ->middleware('web');
    }
}
