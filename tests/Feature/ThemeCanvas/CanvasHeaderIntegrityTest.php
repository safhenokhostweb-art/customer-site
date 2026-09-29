<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * Phase 4.1 — end-to-end H1 integrity detection on the real public Canvas
 * render.
 *
 * Covers the manual verification this phase was written for: multiple <h1>
 * blocks authored on a Canvas page must produce a log entry, and the page
 * must still render exactly as before.
 *
 * Logging is observed through Log::listen() rather than Log::spy() so the real
 * logger keeps running — a spied facade would also swallow genuine render
 * errors and let this test pass for the wrong reason.
 */
class CanvasHeaderIntegrityTest extends TestCase
{
    use RefreshDatabase, SeedsValidLicenseWithShop;

    /** @var array<int, MessageLogged> */
    private array $logged = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->markSiteAsInstalled();
        $this->logged = [];

        Log::listen(function (MessageLogged $event) {
            $this->logged[] = $event;
        });
    }

    private function installThemeWithBlocks(array $blocks): void
    {
        Setting::set('active_canvas_theme_data', json_encode([
            'name' => 'Test Theme', 'slug' => 'test-theme',
            'header_blocks' => [], 'footer_blocks' => [],
            'pages' => [['key' => 'home', 'title' => 'خانه', 'blocks' => $blocks]],
            'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');
        Setting::set('active_canvas_theme_name', 'Test Theme', 'theme');
    }

    private function headingBlock(string $id, string $text, string $level): array
    {
        return ['id' => $id, 'type' => 'heading', 'props' => ['text' => $text, 'level' => $level]];
    }

    /**
     * @return array<int, MessageLogged>
     */
    private function integrityViolations(): array
    {
        return array_values(array_filter(
            $this->logged,
            fn (MessageLogged $e) => $e->level === 'warning'
                && str_contains($e->message, 'H1 integrity violation')
        ));
    }

    // ── Detection ───────────────────────────────────────────────────────────

    public function test_multiple_h1_blocks_on_a_canvas_page_produce_a_log_entry(): void
    {
        $this->installThemeWithBlocks([
            $this->headingBlock('h1a', 'عنوان اول', '1'),
            $this->headingBlock('h2a', 'عنوان دوم', '2'),
            $this->headingBlock('h1b', 'عنوان سوم', '1'),
        ]);

        $response = $this->get('/');
        $response->assertOk();

        $violations = $this->integrityViolations();

        $this->assertCount(1, $violations, 'exactly one violation entry expected');
        $this->assertSame('Canvas H1 integrity violation: multiple H1 tags detected', $violations[0]->message);
        $this->assertSame(2, $violations[0]->context['h1_count']);
        // canvasPageKey is the page's identity inside the theme data. A
        // list-shaped `pages` array means that identity is the array index
        // (ThemeCanvasPageController::findHomePage()'s $stringKey), which is
        // the same value the admin edit toolbar already deep-links with.
        $this->assertSame('0', $violations[0]->context['page_id']);
        $this->assertSame('multiple_h1', $violations[0]->context['violation']);
    }

    public function test_a_canvas_page_with_no_h1_produces_a_missing_h1_log_entry(): void
    {
        $this->installThemeWithBlocks([
            ['id' => 't1', 'type' => 'text', 'props' => ['content' => 'متن صفحه']],
        ]);

        $this->get('/')->assertOk();

        $violations = $this->integrityViolations();

        $this->assertCount(1, $violations);
        $this->assertSame('Canvas H1 integrity violation: missing H1 tag', $violations[0]->message);
        $this->assertSame(0, $violations[0]->context['h1_count']);
        $this->assertSame('missing_h1', $violations[0]->context['violation']);
    }

    public function test_a_correct_single_h1_page_logs_nothing(): void
    {
        $this->installThemeWithBlocks([
            $this->headingBlock('h1a', 'عنوان یکتا', '1'),
            $this->headingBlock('h2a', 'زیرعنوان', '2'),
        ]);

        $this->get('/')->assertOk();

        $this->assertSame([], $this->integrityViolations());
    }

    public function test_the_log_identifies_the_page_key_not_a_numeric_id(): void
    {
        // A Canvas page has no Eloquent model; canvasPageKey is its only identity.
        Setting::set('active_canvas_theme_data', json_encode([
            'name' => 'Test Theme', 'slug' => 'test-theme',
            'header_blocks' => [], 'footer_blocks' => [],
            'pages' => [
                // An explicit `slug` is what /page/{slug} resolves against
                // (findPage() reads $page['slug'] ?? $stringKey), so a
                // list-shaped pages array needs one to be addressable.
                ['slug' => 'home', 'title' => 'خانه', 'blocks' => [$this->headingBlock('a', 'یک', '1')]],
                ['slug' => 'about-us', 'title' => 'درباره', 'blocks' => [
                    $this->headingBlock('b', 'یک', '1'),
                    $this->headingBlock('c', 'دو', '1'),
                ]],
            ],
            'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');
        Setting::set('active_canvas_theme_name', 'Test Theme', 'theme');

        $this->get('/page/about-us')->assertOk();

        $violations = $this->integrityViolations();

        $this->assertCount(1, $violations);
        $this->assertSame('1', $violations[0]->context['page_id']);
    }

    // ── Read-only guarantee: the page must be unaffected ────────────────────

    public function test_a_violating_page_still_renders_every_h1_it_authored(): void
    {
        $this->installThemeWithBlocks([
            $this->headingBlock('h1a', 'عنوان اول', '1'),
            $this->headingBlock('h1b', 'عنوان دوم', '1'),
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        // Detection must not silently "fix" the author's markup.
        $this->assertSame(2, preg_match_all('/<h1\b/i', $html));
        $this->assertStringContainsString('عنوان اول', $html);
        $this->assertStringContainsString('عنوان دوم', $html);
    }

    public function test_the_layouts_own_hidden_h1_fallback_is_left_untouched(): void
    {
        $this->installThemeWithBlocks([
            ['id' => 't1', 'type' => 'text', 'props' => ['content' => 'متن صفحه']],
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        // The pre-existing accessibility fallback (canvas-theme.blade.php) must
        // still fire; this phase only reports, it does not change the markup.
        //
        // As of Phase 4.2 the element carries class="visually-hidden" instead
        // of the old inline style, so the class is what is asserted here — the
        // old `clip:rect(...)` substring would now match the STYLESHEET and
        // pass even if the element had no hiding mechanism at all.
        $this->assertSame(1, preg_match_all('/<h1\b[^>]*class="visually-hidden"/i', $html));
    }

    public function test_a_violating_page_still_serves_its_json_ld_unchanged(): void
    {
        $this->installThemeWithBlocks([
            $this->headingBlock('h1a', 'عنوان اول', '1'),
            $this->headingBlock('h1b', 'عنوان دوم', '1'),
            ['id' => 'faq1', 'type' => 'faq', 'props' => ['items' => [
                ['question' => 'سوال', 'answer' => 'پاسخ'],
            ]]],
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        // Phase 4.1 must not disturb the C-1 structured data work.
        $this->assertStringContainsString('"@type":"FAQPage"', $html);
        $this->assertStringContainsString('"@type":"WebPage"', $html);
    }
}
