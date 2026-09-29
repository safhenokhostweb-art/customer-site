<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SeedsValidLicenseWithShop;
use Tests\TestCase;

/**
 * SEO audit C-1 — end-to-end proof that the public Canvas render actually
 * SERVES the page-level structured data, not just that a unit test can
 * build it.
 *
 * The unit test (tests/Unit/CanvasStructuredDataTest.php) proves the graph
 * builder and the <x-seo-meta> plumbing. This one drives the real
 * HTTP request through ThemeCanvasPageController and reads the real <head>,
 * using the same install pattern as the sibling ThemeCanvas feature tests.
 *
 * Requires a database (RefreshDatabase) only because serving the public route
 * requires the settings/tenant/license tables — the project's established
 * pattern for this surface.
 */
class CanvasStructuredDataRenderTest extends TestCase
{
    use RefreshDatabase, SeedsValidLicenseWithShop;

    protected function setUp(): void
    {
        parent::setUp();
        $this->markSiteAsInstalled();
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

    /** @return array<int,array<string,mixed>> decoded JSON-LD graphs from the response head */
    private function graphsFrom(string $html): array
    {
        preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $m);

        $graphs = [];
        foreach ($m[1] as $body) {
            $decoded = json_decode($body, true);
            $this->assertIsArray($decoded, 'every JSON-LD body must decode; got: ' . $body);
            $graphs[] = $decoded;
        }

        return $graphs;
    }

    private function typesIn(array $graphs): array
    {
        return array_values(array_filter(array_column($graphs, '@type')));
    }

    public function test_public_canvas_page_serves_web_page_schema_in_its_head(): void
    {
        $this->installThemeWithBlocks([
            ['id' => 't1', 'type' => 'text', 'props' => ['content' => 'متن یکتای صفحهٔ خانه']],
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $types = $this->typesIn($this->graphsFrom($response->getContent()));

        $this->assertContains('WebPage', $types);

        $webPage = collect($this->graphsFrom($response->getContent()))->firstWhere('@type', 'WebPage');
        $this->assertSame(url('/'), $webPage['url']);
        $this->assertNotSame('', trim((string) ($webPage['name'] ?? '')));
    }

    public function test_public_canvas_page_serves_faq_page_schema_for_a_real_faq_block(): void
    {
        $this->installThemeWithBlocks([
            ['id' => 'faq1', 'type' => 'faq', 'props' => [
                'title' => 'سوالات متداول',
                'items' => [
                    ['question' => 'ارسال دارید؟', 'answer' => 'بله، با پست پیشتاز'],
                    ['question' => 'بدون پاسخ', 'answer' => ''],
                ],
            ]],
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $graphs = $this->graphsFrom($response->getContent());
        $types = $this->typesIn($graphs);

        $this->assertContains('FAQPage', $types);

        $faq = collect($graphs)->firstWhere('@type', 'FAQPage');
        $this->assertCount(1, $faq['mainEntity'], 'the incomplete pair must be dropped');
        $this->assertSame('ارسال دارید؟', $faq['mainEntity'][0]['name']);
        $this->assertSame('بله، با پست پیشتاز', $faq['mainEntity'][0]['acceptedAnswer']['text']);
    }

    public function test_site_level_graphs_appear_exactly_once_on_a_canvas_page(): void
    {
        $this->installThemeWithBlocks([
            ['id' => 'faq1', 'type' => 'faq', 'props' => ['items' => [
                ['question' => 'سوال', 'answer' => 'پاسخ'],
            ]]],
        ]);

        $types = $this->typesIn($this->graphsFrom($this->get('/')->assertOk()->getContent()));

        foreach (['Organization', 'WebSite', 'WebPage', 'FAQPage'] as $type) {
            $this->assertSame(1, count(array_keys($types, $type, true)), $type . ' must appear exactly once');
        }
    }

    public function test_no_faq_page_is_served_when_the_block_has_no_usable_pair(): void
    {
        $this->installThemeWithBlocks([
            ['id' => 'faq1', 'type' => 'faq', 'props' => ['items' => [
                ['question' => 'سوال', 'answer' => '  '],
            ]]],
        ]);

        $types = $this->typesIn($this->graphsFrom($this->get('/')->assertOk()->getContent()));

        $this->assertNotContains('FAQPage', $types);
        $this->assertContains('WebPage', $types);
    }

    public function test_no_commercial_schema_is_fabricated_from_a_product_block(): void
    {
        $this->installThemeWithBlocks([
            ['id' => 'pg1', 'type' => 'product_grid', 'props' => ['columns' => 3]],
        ]);

        $types = $this->typesIn($this->graphsFrom($this->get('/')->assertOk()->getContent()));

        foreach (['Product', 'Offer', 'Review', 'AggregateRating', 'BreadcrumbList', 'ItemList'] as $forbidden) {
            $this->assertNotContains($forbidden, $types, $forbidden . ' must not be fabricated');
        }
    }

    public function test_legacy_shop_page_schema_is_untouched_by_the_canvas_change(): void
    {
        // A Canvas-installed site still serves /shop/* through the legacy
        // theme views. That path must keep its own Product graph and must not
        // gain a second WebPage/FAQPage from the Canvas pipeline.
        $this->installThemeWithBlocks([
            ['id' => 'faq1', 'type' => 'faq', 'props' => ['items' => [
                ['question' => 'سوال', 'answer' => 'پاسخ'],
            ]]],
        ]);

        $types = $this->typesIn($this->graphsFrom($this->get('/')->assertOk()->getContent()));

        $this->assertNotContains('Product', $types);
        $this->assertSame(1, count(array_keys($types, 'Organization', true)));
    }
}
