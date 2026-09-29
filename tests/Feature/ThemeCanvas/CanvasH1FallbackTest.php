<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * SEO Phase 4.2 — the H1 safety net.
 *
 * Every assertion parses the RESPONSE with DOMDocument and inspects real
 * elements, rather than matching substrings. That distinction is the whole
 * point of this phase: the pre-4.2 fallback was guarded by a regex, and a
 * substring assertion would happily pass against decoy markup in a <script>
 * or an attribute while the page was still broken.
 */
class CanvasH1FallbackTest extends TestCase
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

    private function installThemeWithBlocks(array $blocks, string $title = 'خانه'): void
    {
        Setting::set('active_canvas_theme_data', json_encode([
            'name' => 'Test Theme', 'slug' => 'test-theme',
            'header_blocks' => [], 'footer_blocks' => [],
            'pages' => [['slug' => 'home', 'title' => $title, 'blocks' => $blocks]],
            'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');
        Setting::set('active_canvas_theme_name', 'Test Theme', 'theme');

        // ThemeCanvasPageController::loadThemeData() reads through
        // Cache::remember('canvas_theme_data', 60, ...), so a second install
        // inside one test would otherwise be served the FIRST theme's blocks.
        Cache::forget('canvas_theme_data');
    }

    private function headingBlock(string $id, string $text, string $level): array
    {
        return ['id' => $id, 'type' => 'heading', 'props' => ['text' => $text, 'level' => $level]];
    }

    private function parse(string $html): \DOMDocument
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return $document;
    }

    /**
     * The real <h1> elements inside the <main> content area.
     *
     * @return array<int, \DOMElement>
     */
    private function mainH1Elements(string $html): array
    {
        $main = $this->parse($html)->getElementsByTagName('main')->item(0);
        $this->assertNotNull($main, 'the canvas layout must always render a <main>');

        $found = [];
        foreach ($main->getElementsByTagName('h1') as $node) {
            $found[] = $node;
        }

        return $found;
    }

    private function integrityViolations(): array
    {
        return array_values(array_filter(
            $this->logged,
            fn (MessageLogged $e) => $e->level === 'warning' && str_contains($e->message, 'H1 integrity violation')
        ));
    }

    // ── Test case 1 (Valid): one H1 → no fallback ───────────────────────────

    public function test_a_page_with_exactly_one_h1_receives_no_fallback(): void
    {
        $this->installThemeWithBlocks([
            $this->headingBlock('h1a', 'عنوان اصلی', '1'),
            $this->headingBlock('h2a', 'زیرعنوان', '2'),
        ]);

        $h1s = $this->mainH1Elements($this->get('/')->assertOk()->getContent());

        $this->assertCount(1, $h1s);
        $this->assertSame('عنوان اصلی', trim($h1s[0]->textContent));
        $this->assertNotSame('visually-hidden', $h1s[0]->getAttribute('class'));
    }

    // ── Test case 2 (Missing): zero H1 → fallback injected ──────────────────

    public function test_a_page_with_no_h1_receives_exactly_one_fallback(): void
    {
        $this->installThemeWithBlocks([
            ['id' => 't1', 'type' => 'text', 'props' => ['content' => 'متن صفحه']],
        ]);

        $h1s = $this->mainH1Elements($this->get('/')->assertOk()->getContent());

        $this->assertCount(1, $h1s, 'the safety net must produce exactly one H1');
        $this->assertSame('visually-hidden', $h1s[0]->getAttribute('class'));
    }

    public function test_the_fallback_is_the_first_element_inside_main(): void
    {
        $this->installThemeWithBlocks([
            ['id' => 't1', 'type' => 'text', 'props' => ['content' => 'متن صفحه']],
        ]);

        $main = $this->parse($this->get('/')->assertOk()->getContent())
            ->getElementsByTagName('main')->item(0);

        $firstElement = null;
        foreach ($main->childNodes as $node) {
            if ($node->nodeType === XML_ELEMENT_NODE) {
                $firstElement = $node;
                break;
            }
        }

        $this->assertNotNull($firstElement);
        $this->assertSame('h1', $firstElement->nodeName, 'the fallback must lead the content area');
    }

    // ── Test case 3 (Multiple): two H1 → no fallback, log only ─────────────

    public function test_a_page_with_two_h1_receives_no_fallback_and_only_the_log(): void
    {
        $this->installThemeWithBlocks([
            $this->headingBlock('h1a', 'عنوان اول', '1'),
            $this->headingBlock('h1b', 'عنوان دوم', '1'),
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        // The author's structure is left completely alone.
        $this->assertCount(2, $this->mainH1Elements($html));
        foreach ($this->mainH1Elements($html) as $h1) {
            $this->assertNotSame('visually-hidden', $h1->getAttribute('class'));
        }

        // …and the Phase 4.1 warning still fires.
        $violations = $this->integrityViolations();
        $this->assertCount(1, $violations);
        $this->assertSame('Canvas H1 integrity violation: multiple H1 tags detected', $violations[0]->message);
    }

    // ── Test case 4 (SEO): the fallback carries the page title ──────────────

    public function test_the_injected_fallback_carries_the_page_title(): void
    {
        $this->installThemeWithBlocks(
            [['id' => 't1', 'type' => 'text', 'props' => ['content' => 'متن صفحه']]],
            'درباره ما',
        );

        $h1s = $this->mainH1Elements($this->get('/')->assertOk()->getContent());

        $this->assertCount(1, $h1s);
        $this->assertSame('درباره ما', trim($h1s[0]->textContent));
    }

    // ── The regression this phase actually fixes ────────────────────────────

    public function test_a_decoy_h1_in_markup_no_longer_suppresses_the_real_fallback(): void
    {
        // The pre-4.2 regex guard was `!preg_match('/<h1\b/i', $contentHtml)`.
        // A literal "<h1" inside a <script> or an attribute satisfied that
        // regex, so the fallback was suppressed while the page genuinely had
        // ZERO heading elements. Only a DOM-aware count gets this right.
        $this->installThemeWithBlocks([
            ['id' => 't1', 'type' => 'text', 'props' => [
                'content' => '<p data-note="&lt;h1&gt;">متن</p><script>var t = "<h1>decoy</h1>";</script>',
            ]],
        ]);

        $h1s = $this->mainH1Elements($this->get('/')->assertOk()->getContent());

        $this->assertCount(1, $h1s, 'the real page had no H1, so the safety net must fire');
        $this->assertSame('visually-hidden', $h1s[0]->getAttribute('class'));
    }

    public function test_the_visually_hidden_class_is_actually_defined_in_the_stylesheet(): void
    {
        // A Canvas theme MUST be installed: with none, ThemeCanvasPageController
        // delegates to HomeController and the legacy layout renders, which
        // does not include the Canvas style contract at all.
        $this->installThemeWithBlocks([
            ['id' => 't1', 'type' => 'text', 'props' => ['content' => 'متن صفحه']],
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        // Guards the specific trap: a class referenced with no matching rule
        // renders the H1 VISIBLE, which is a visible regression on every page
        // that relies on the safety net.
        $this->assertStringContainsString('.visually-hidden', $html);
        $this->assertMatchesRegularExpression(
            '/\.visually-hidden\s*\{[^}]*position:\s*absolute/i',
            $html,
            '.visually-hidden must carry the hiding rules, not just exist',
        );
        $this->assertMatchesRegularExpression(
            '/\.visually-hidden\s*\{[^}]*clip:\s*rect/i',
            $html,
        );
    }

    public function test_the_whole_document_always_ends_up_with_exactly_one_h1(): void
    {
        // The end-to-end promise of the phase, stated on real markup.
        $scenarios = [
            'zero h1'      => [['id' => 't1', 'type' => 'text', 'props' => ['content' => 'متن']]],
            'one h1'       => [$this->headingBlock('a', 'یک', '1')],
            'two h1'       => [$this->headingBlock('a', 'یک', '1'), $this->headingBlock('b', 'دو', '1')],
        ];

        foreach ($scenarios as $label => $blocks) {
            $this->installThemeWithBlocks($blocks);
            $html = $this->get('/')->assertOk()->getContent();

            // Two authored H1s stay two: the phase must not paper over that.
            $expected = $label === 'two h1' ? 2 : 1;
            $this->assertSame(
                $expected,
                $this->parse($html)->getElementsByTagName('h1')->length,
                $label,
            );
        }
    }
}
