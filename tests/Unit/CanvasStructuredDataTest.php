<?php

namespace Tests\Unit;

use App\Helpers\JsonLd;
use App\Services\ThemeCanvas\CanvasStructuredData;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * SEO audit C-1 — structured data on the PUBLIC Canvas render path.
 *
 * Before this change the public Canvas page emitted exactly two JSON-LD
 * graphs: Organization and WebSite (both from <x-seo-meta>). This test class
 * pins the two page-level graphs that the page's own REAL data can support —
 * WebPage and FAQPage — and, just as importantly, pins what must NOT appear:
 * invented Product/Review/Breadcrumb data, or a second copy of the
 * site-level pair.
 *
 * No database and no external service is required: JsonLd and
 * CanvasStructuredData are pure functions of their inputs.
 */
class CanvasStructuredDataTest extends TestCase
{
    private function graphs(array $blocks, array $seoMeta = [], ?string $lang = 'fa'): array
    {
        return app(CanvasStructuredData::class)->for($blocks, $seoMeta, $lang);
    }

    private function seoMeta(array $overrides = []): array
    {
        return $overrides + [
            'title' => 'دربارهٔ ما',
            'description' => 'توضیح صفحه.',
            'canonical' => 'https://example.com/page/about',
            'robots' => 'index, follow',
        ];
    }

    // ── WebPage ──────────────────────────────────────────────────────────

    public function test_web_page_is_always_built_from_the_resolved_seo_meta(): void
    {
        $graphs = $this->graphs([], $this->seoMeta());

        $this->assertCount(1, $graphs);
        $this->assertSame('https://schema.org', $graphs[0]['@context']);
        $this->assertSame('WebPage', $graphs[0]['@type']);
        $this->assertSame('دربارهٔ ما', $graphs[0]['name']);
        $this->assertSame('https://example.com/page/about', $graphs[0]['url']);
        $this->assertSame('توضیح صفحه.', $graphs[0]['description']);
        $this->assertSame('fa', $graphs[0]['inLanguage']);
    }

    public function test_web_page_matches_the_existing_json_ld_helper_shape(): void
    {
        $graph = $this->graphs([], $this->seoMeta())[0];
        unset($graph['inLanguage']);

        $this->assertSame(JsonLd::webPage('دربارهٔ ما', 'https://example.com/page/about', 'توضیح صفحه.'), $graph);
    }

    public function test_in_language_is_omitted_when_absent_blank_or_not_a_real_language_tag(): void
    {
        foreach ([null, '', '   ', 'fa dir="x', 'a', '123'] as $bad) {
            $graph = $this->graphs([], $this->seoMeta(), $bad)[0];

            $this->assertArrayNotHasKey('inLanguage', $graph, 'lang=' . var_export($bad, true));
        }
    }

    public function test_a_real_language_subtag_is_kept(): void
    {
        $graph = $this->graphs([], $this->seoMeta(), 'en-GB')[0];

        $this->assertSame('en-GB', $graph['inLanguage']);
    }

    public function test_no_web_page_when_title_or_canonical_is_missing(): void
    {
        $this->assertSame([], $this->graphs([], $this->seoMeta(['title' => '   '])));
        $this->assertSame([], $this->graphs([], $this->seoMeta(['canonical' => ''])));
    }

    public function test_blank_description_is_omitted_rather_than_emitted_empty(): void
    {
        $graph = $this->graphs([], $this->seoMeta(['description' => '  ']))[0];

        $this->assertArrayNotHasKey('description', $graph);
    }

    // ── FAQPage: valid, empty, incomplete ────────────────────────────────

    public function test_faq_page_is_built_from_a_real_faq_block(): void
    {
        $graphs = $this->graphs([
            ['type' => 'section', 'props' => [], 'children' => [
                ['type' => 'faq', 'props' => ['title' => 'سوالات', 'items' => [
                    ['question' => 'ارسال دارید؟', 'answer' => 'بله، با پست'],
                    ['question' => 'ارسال بین‌المللی؟', 'answer' => 'تا ۴۵ روز'],
                ]]],
            ]],
        ], $this->seoMeta());

        $this->assertCount(2, $graphs);
        $this->assertSame('WebPage', $graphs[0]['@type']);
        $this->assertSame('FAQPage', $graphs[1]['@type']);
        $this->assertCount(2, $graphs[1]['mainEntity']);
        $this->assertSame('ارسال دارید؟', $graphs[1]['mainEntity'][0]['name']);
        $this->assertSame('بله، با پست', $graphs[1]['mainEntity'][0]['acceptedAnswer']['text']);
    }

    public function test_faq_page_is_omitted_when_the_block_carries_no_usable_pair(): void
    {
        $cases = [
            'no items key'      => ['type' => 'faq', 'props' => ['title' => 'سوالات']],
            'empty items'       => ['type' => 'faq', 'props' => ['items' => []]],
            'blank question'    => ['type' => 'faq', 'props' => ['items' => [['question' => '  ', 'answer' => 'پاسخ']]]],
            'blank answer'      => ['type' => 'faq', 'props' => ['items' => [['question' => 'سوال', 'answer' => '']]]],
            'missing answer'    => ['type' => 'faq', 'props' => ['items' => [['question' => 'سوال']]]],
            'scalar items'      => ['type' => 'faq', 'props' => ['items' => 'not-an-array']],
            'scalar prop'       => ['type' => 'faq', 'props' => 'not-an-array'],
            'non-array entries' => ['type' => 'faq', 'props' => ['items' => ['bare-string', 42]]],
        ];

        foreach ($cases as $label => $block) {
            $graphs = $this->graphs([$block], $this->seoMeta());

            $this->assertCount(1, $graphs, $label);
            $this->assertSame('WebPage', $graphs[0]['@type'], $label);
        }
    }

    public function test_faq_page_drops_incomplete_pairs_but_keeps_the_valid_ones(): void
    {
        $graphs = $this->graphs([
            ['type' => 'faq', 'props' => ['items' => [
                ['question' => 'بدون پاسخ'],
                ['question' => '', 'answer' => 'بدون سوال'],
                ['question' => 'معتبر', 'answer' => 'پاسخ معتبر'],
            ]]],
        ], $this->seoMeta());

        $this->assertCount(2, $graphs);
        $this->assertCount(1, $graphs[1]['mainEntity']);
        $this->assertSame('معتبر', $graphs[1]['mainEntity'][0]['name']);
    }

    public function test_faq_items_authored_as_a_json_string_are_still_read(): void
    {
        $graphs = $this->graphs([
            ['type' => 'faq', 'props' => [
                'items' => json_encode([['question' => 'سوال', 'answer' => 'پاسخ']], JSON_UNESCAPED_UNICODE),
            ]],
        ], $this->seoMeta());

        $this->assertSame('FAQPage', $graphs[1]['@type']);
        $this->assertSame('سوال', $graphs[1]['mainEntity'][0]['name']);
    }

    public function test_a_keyed_prop_map_is_not_mistaken_for_faq_content(): void
    {
        $graphs = $this->graphs([
            ['type' => 'faq', 'props' => ['items' => ['question' => 'سوال', 'answer' => 'پاسخ']]],
        ], $this->seoMeta());

        $this->assertCount(1, $graphs);
        $this->assertSame('WebPage', $graphs[0]['@type']);
    }

    public function test_faq_nested_deep_inside_sections_is_found(): void
    {
        $graphs = $this->graphs([
            ['type' => 'section', 'props' => [], 'children' => [
                ['type' => 'column', 'props' => [], 'children' => [
                    ['type' => 'faq', 'props' => ['items' => [
                        ['question' => 'عمیق', 'answer' => 'پاسخ'],
                    ]]],
                ]],
            ]],
        ], $this->seoMeta());

        $this->assertSame('FAQPage', $graphs[1]['@type']);
    }

    public function test_non_faq_blocks_never_contribute_faq_entities(): void
    {
        $graphs = $this->graphs([
            ['type' => 'product_grid', 'props' => ['items' => [
                ['question' => 'سوال', 'answer' => 'پاسخ'],
            ]]],
            ['type' => 'accordion', 'props' => ['items' => [
                ['question' => 'سوال', 'answer' => 'پاسخ'],
            ]]],
        ], $this->seoMeta());

        $this->assertCount(1, $graphs);
        $this->assertSame('WebPage', $graphs[0]['@type']);
    }

    public function test_malformed_block_entries_do_not_break_the_walk(): void
    {
        $graphs = $this->graphs(
            ['bare-string', 42, null, ['type' => 'faq', 'props' => ['items' => [
                ['question' => 'سالم', 'answer' => 'پاسخ'],
            ]]]],
            $this->seoMeta(),
        );

        $this->assertSame('FAQPage', $graphs[1]['@type']);
    }

    // ── anti-fabrication ─────────────────────────────────────────────────

    public function test_no_page_level_graph_type_is_emitted_that_the_page_has_no_real_data_for(): void
    {
        $graphs = $this->graphs([
            ['type' => 'faq', 'props' => ['items' => [['question' => 'سوال', 'answer' => 'پاسخ']]]],
            ['type' => 'product_grid', 'props' => ['items' => [['title' => 'کالا']]]],
            ['type' => 'testimonials', 'props' => ['items' => [['name' => 'مشتری']]]],
            ['type' => 'breadcrumb', 'props' => []],
        ], $this->seoMeta());

        $types = array_column($graphs, '@type');

        $this->assertSame(['WebPage', 'FAQPage'], $types);
        foreach (['Product', 'Offer', 'Review', 'AggregateRating', 'BreadcrumbList', 'Article', 'ItemList', 'LocalBusiness'] as $forbidden) {
            $this->assertNotContains($forbidden, $types, $forbidden . ' must not be fabricated');
        }
    }

    // ── rendering: valid JSON, no duplicates, metadata preserved ─────────

    public function test_every_graph_survives_a_json_round_trip(): void
    {
        $graphs = $this->graphs([
            ['type' => 'faq', 'props' => ['items' => [['question' => 'سوال "ویژه"', 'answer' => "پاسخ\nچندخطی"]]]],
        ], $this->seoMeta());

        foreach ($graphs as $graph) {
            $encoded = json_encode($graph, JSON_UNESCAPED_UNICODE);
            $this->assertIsString($encoded);
            $this->assertSame($graph, json_decode($encoded, true), 'graph must round-trip through JSON');
        }
    }

    public function test_rendered_component_emits_exactly_one_of_each_graph_type(): void
    {
        $schema = $this->graphs([
            ['type' => 'faq', 'props' => ['items' => [['question' => 'سوال', 'answer' => 'پاسخ']]]],
        ], $this->seoMeta());

        $html = Blade::render(
            '<x-seo-meta :title="$title" :schema="$schema" />',
            ['title' => 'T', 'schema' => $schema],
        );

        $this->assertSame(1, substr_count($html, '"@type":"Organization"'));
        $this->assertSame(1, substr_count($html, '"@type":"WebSite"'));
        $this->assertSame(1, substr_count($html, '"@type":"WebPage"'));
        $this->assertSame(1, substr_count($html, '"@type":"FAQPage"'));

        // Every <script type="application/ld+json"> body must be decodable.
        preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m);
        $this->assertCount(4, $m[1]);
        foreach ($m[1] as $body) {
            $this->assertIsArray(json_decode($body, true), 'undecodable JSON-LD body: ' . $body);
        }
    }

    public function test_empty_schema_renders_exactly_the_previous_output(): void
    {
        $withEmpty = Blade::render('<x-seo-meta :title="\'T\'" :schema="[]" />', ['schema' => []]);
        $without = Blade::render('<x-seo-meta :title="\'T\'" />');

        $this->assertSame($without, $withEmpty);
    }

    public function test_existing_metadata_tags_are_unchanged_by_the_schema_input(): void
    {
        $base = ['title' => 'T', 'description' => 'D', 'canonical' => 'https://example.com/c'];

        $withSchema = Blade::render(
            '<x-seo-meta :title="$title" :description="$description" :canonical="$canonical" :schema="$schema" />',
            $base + ['schema' => [['@context' => 'https://schema.org', '@type' => 'WebPage', 'name' => 'n', 'url' => 'u']]],
        );
        $withoutSchema = Blade::render(
            '<x-seo-meta :title="$title" :description="$description" :canonical="$canonical" />',
            $base,
        );

        foreach ([
            '<meta name="description" content="D">',
            '<link rel="canonical" href="https://example.com/c">',
            '<meta property="og:title" content="T">',
            '<meta property="og:url" content="https://example.com/c">',
            '<meta property="og:description" content="D">',
            '<meta name="twitter:title" content="T">',
        ] as $tag) {
            $this->assertStringContainsString($tag, $withSchema);
            $this->assertStringContainsString($tag, $withoutSchema);
        }
    }

    public function test_hostile_cannot_break_out_of_the_json_ld_script(): void
    {
        $schema = $this->graphs([
            ['type' => 'faq', 'props' => ['items' => [[
                'question' => '</script><script>alert(1)</script>',
                'answer' => '"><img src=x onerror=alert(1)>',
            ]]],
        ]], $this->seoMeta());

        $html = Blade::render(
            '<x-seo-meta :title="$title" :schema="$schema" />',
            ['title' => 'T', 'schema' => $schema],
        );

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('onerror=alert(1)>', $html);

        // Organization + WebSite + WebPage + FAQPage — the hostile content
        // stays inside its JSON string and adds no extra script tags.
        $this->assertSame(4, substr_count($html, '<script type="application/ld+json">'));
    }

    // ── wiring: the public layout must pass the graphs through ───────────

    public function test_canvas_layout_passes_schema_to_the_seo_meta_component(): void
    {
        $contents = file_get_contents(resource_path('views/layouts/canvas-theme.blade.php'));

        $this->assertStringContainsString(':schema="($schema ?? [])"', $contents);
    }

    public function test_canvas_controller_supplies_the_schema_next_to_seo_meta(): void
    {
        $contents = file_get_contents(app_path('Http/Controllers/Front/ThemeCanvasPageController.php'));

        $this->assertStringContainsString('CanvasStructuredData', $contents);
        $this->assertStringContainsString("'schema'", $contents);
        $this->assertStringContainsString("'seoMeta'", $contents);
    }

    public function test_the_json_ld_helper_method_is_additive_only(): void
    {
        // The pre-existing helpers legacy pages depend on must be untouched.
        foreach (['organization', 'webSite', 'webPage', 'article', 'product', 'breadcrumb'] as $method) {
            $this->assertTrue(method_exists(JsonLd::class, $method), $method);
        }

        $this->assertSame([], JsonLd::faqPage([]));
        $this->assertSame([], JsonLd::faqPage([['question' => '', 'answer' => '']]));
    }
}
