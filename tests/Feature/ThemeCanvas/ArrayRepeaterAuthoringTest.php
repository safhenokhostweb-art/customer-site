<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use App\Models\User;
use App\Services\ThemeCanvas\ThemeInstanceRevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TASK 2A-CONTINUATION (Full Product Commercial Audit): closes
 * EXPOSED_INCOMPLETE_ARRAY_AUTHORING for the 10 canonical types whose
 * array-shaped content was authored via a single raw-JSON `textarea`
 * field instead of a real add/remove/reorder Canvas repeater —
 * `features_grid.features`, `stats_row.stats`, `client_logos.logos`,
 * `process_steps.steps`, `skills_bars.skills`,
 * `testimonials_slider.items`, `trust_badges.badges`,
 * `pricing_cards.plans`, `bento_grid.items`, `services_cards.services`.
 * (BLD-11..13 — the other 7 canonical types from the authoritative Task 2A
 * report: nav_header/nav_footer/about_section/service_cards/
 * project_cards/contact_info/faq — were already converted before this
 * pass; not re-tested here.)
 *
 * Each renderer already defensively accepted either a real PHP array or a
 * legacy JSON-encoded string (`is_array($raw) ? $raw : json_decode(...)`)
 * — this test proves BOTH shapes still render identically (no regression
 * for existing saved theme content) and that BlockRegistry's live schema
 * now actually exposes a `repeater` control (with per-item `fields`) for
 * each of these props, which is what the Canvas authoring UI reads to
 * decide whether to show a real repeater or a raw textarea.
 */
class ArrayRepeaterAuthoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true, 'site.platform_site_id' => 601]);
    }

    private function renderer(): \App\Services\ThemeCanvas\ThemeCanvasRenderer
    {
        $renderer = app(\App\Services\ThemeCanvas\ThemeCanvasRenderer::class);
        $renderer->setThemeData(['pages' => []]);
        return $renderer;
    }

    /** @return array<int, array{0: string, 1: string}> [type, arrayFieldName] */
    public static function convertedFields(): array
    {
        return [
            ['features_grid', 'features'],
            ['stats_row', 'stats'],
            ['client_logos', 'logos'],
            ['process_steps', 'steps'],
            ['skills_bars', 'skills'],
            ['testimonials_slider', 'items'],
            ['trust_badges', 'badges'],
            ['pricing_cards', 'plans'],
            ['bento_grid', 'items'],
            ['services_cards', 'services'],
        ];
    }

    /**
     * @dataProvider convertedFields
     */
    public function test_schema_now_declares_a_real_repeater_with_fields(string $type, string $field): void
    {
        $schema = \App\Services\ThemeCanvas\BlockRegistry::all()[$type]['schema'][$field] ?? null;

        $this->assertNotNull($schema, "{$type}.{$field} schema must exist");
        $this->assertSame('repeater', $schema['type'], "{$type}.{$field} must be a repeater, not a raw textarea");
        $this->assertNotEmpty($schema['fields'] ?? [], "{$type}.{$field} must declare per-item fields");
    }

    public function test_stats_row_renders_identically_for_array_and_legacy_json_string(): void
    {
        $items = [
            ['value' => 42, 'suffix' => '+', 'label' => 'مشتری راضی'],
        ];

        $htmlArray = $this->renderer()->renderBlock([
            'id' => 's1', 'type' => 'stats_row', 'props' => ['stats' => $items],
        ]);
        $htmlJson = $this->renderer()->renderBlock([
            'id' => 's2', 'type' => 'stats_row', 'props' => ['stats' => json_encode($items, JSON_UNESCAPED_UNICODE)],
        ]);

        foreach ([$htmlArray, $htmlJson] as $html) {
            $this->assertStringContainsString('42', $html);
            $this->assertStringContainsString('مشتری راضی', $html);
        }
    }

    public function test_client_logos_renders_authored_array(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'l1', 'type' => 'client_logos',
            'props' => ['logos' => [['name' => 'برند تستی الف'], ['name' => 'برند تستی ب']]],
        ]);

        $this->assertStringContainsString('برند تستی الف', $html);
        $this->assertStringContainsString('برند تستی ب', $html);
    }

    public function test_process_steps_renders_authored_array(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'p1', 'type' => 'process_steps',
            'props' => ['steps' => [['number' => '۱', 'title' => 'مرحله تستی', 'text' => 'توضیح تستی']]],
        ]);

        $this->assertStringContainsString('مرحله تستی', $html);
        $this->assertStringContainsString('توضیح تستی', $html);
    }

    public function test_skills_bars_renders_authored_array(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'sk1', 'type' => 'skills_bars',
            'props' => ['skills' => [['name' => 'مهارت تستی', 'percent' => 77]]],
        ]);

        $this->assertStringContainsString('مهارت تستی', $html);
        $this->assertStringContainsString('77', $html);
    }

    public function test_testimonials_slider_renders_authored_array(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 't1', 'type' => 'testimonials_slider',
            'props' => ['items' => [['name' => 'مشتری تستی', 'role' => 'مدیر', 'text' => 'نظر تستی', 'rating' => 4]]],
        ]);

        $this->assertStringContainsString('مشتری تستی', $html);
        $this->assertStringContainsString('نظر تستی', $html);
    }

    public function test_trust_badges_renders_authored_array(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'tb1', 'type' => 'trust_badges',
            'props' => ['badges' => [['icon' => '🎁', 'title' => 'نشان تستی', 'text' => 'متن تستی']]],
        ]);

        $this->assertStringContainsString('نشان تستی', $html);
    }

    public function test_pricing_cards_renders_authored_array_including_nested_features(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'pc1', 'type' => 'pricing_cards',
            'props' => ['plans' => [[
                'name' => 'پلن تستی', 'price' => '۱۰۰', 'period' => '/ ماه',
                'badge' => '', 'highlight' => false,
                // New canonical shape: an array of {text: "..."} objects,
                // exactly what the nested Canvas repeater now persists.
                'features' => [['text' => 'ویژگی تستی یک'], ['text' => 'ویژگی تستی دو']],
                'cta_text' => 'خرید', 'cta_url' => '#',
            ]]],
        ]);

        $this->assertStringContainsString('پلن تستی', $html);
        $this->assertStringContainsString('ویژگی تستی یک', $html);
        $this->assertStringContainsString('ویژگی تستی دو', $html);
    }

    public function test_pricing_cards_features_legacy_plain_string_array_still_renders(): void
    {
        // Backward compatibility: theme content saved before this fix has
        // features as a bare string array (or a JSON-encoded string of
        // one) — both must keep rendering correctly, unchanged.
        $htmlArrayOfStrings = $this->renderer()->renderBlock([
            'id' => 'pc2', 'type' => 'pricing_cards',
            'props' => ['plans' => [['name' => 'قدیمی', 'features' => ['ویژگی قدیمی']]]],
        ]);
        $htmlJsonString = $this->renderer()->renderBlock([
            'id' => 'pc3', 'type' => 'pricing_cards',
            'props' => ['plans' => json_encode([['name' => 'قدیمی۲', 'features' => ['ویژگی قدیمی دو']]], JSON_UNESCAPED_UNICODE)],
        ]);

        $this->assertStringContainsString('ویژگی قدیمی', $htmlArrayOfStrings);
        $this->assertStringContainsString('ویژگی قدیمی دو', $htmlJsonString);
    }

    /**
     * BLD-11..13 closure requirement: schema-driven bounded nested control
     * (no JSON exposed) proven through the REAL AUTHOR → SAVE → RELOAD →
     * REORDER → PUBLISH → PUBLIC RENDER chain — not just a direct renderer
     * call. Uses the actual admin-facing endpoints
     * (theme-design.canvas-save-blocks / -publish) and the actual public
     * route (page.show) a real browser session would hit.
     */
    public function test_pricing_cards_nested_features_full_author_save_reload_reorder_publish_public_render_chain(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $pageKey = 'pricing-e2e-' . uniqid();

        app(ThemeInstanceRevisionService::class)->record([
            'header_blocks' => [], 'footer_blocks' => [],
            'header_variants' => [], 'footer_variants' => [],
            'pages' => [$pageKey => ['slug' => $pageKey, 'title' => 'Pricing E2E', 'blocks' => []]],
        ], ThemeInstanceRevision::TYPE_INSTALL, ['theme_identifier' => 'pricing-e2e-theme-' . uniqid()]);

        $blockOf = fn (array $featureTexts) => [[
            'id' => 'pc-e2e-1', 'type' => 'pricing_cards',
            'props' => [
                'title' => 'پلن‌ها', 'subtitle' => '',
                'plans' => [[
                    'name' => 'پلن تستی', 'price' => '۱۰۰', 'period' => '', 'badge' => '', 'highlight' => '',
                    'features' => array_map(fn ($t) => ['text' => $t], $featureTexts),
                    'cta_text' => 'خرید', 'cta_url' => '#',
                ]],
            ],
        ]];

        // 1. AUTHOR + ADD PLAN + ADD MULTIPLE FEATURES, via the real save endpoint.
        $this->actingAs($admin)->putJson(route('admin.theme-design.canvas-save-blocks'), [
            'page_key' => $pageKey,
            'blocks' => $blockOf(['ویژگی الف', 'ویژگی ب', 'ویژگی ج']),
        ])->assertOk()->assertJson(['success' => true]);

        // 2. RELOAD proof: the draft actually persisted the exact authored order.
        $draft = json_decode(Setting::get('canvas_draft_data'), true);
        $savedFeatures = array_column($draft['pages'][$pageKey]['blocks'][0]['props']['plans'][0]['features'], 'text');
        $this->assertSame(['ویژگی الف', 'ویژگی ب', 'ویژگی ج'], $savedFeatures, 'reload must reflect exactly what was authored/saved');

        // 3. REORDER: re-save with the features in a new order (this is
        // exactly what moveNestedItem() produces client-side — an array
        // with two entries swapped — then persisted through the same
        // save endpoint).
        $this->actingAs($admin)->putJson(route('admin.theme-design.canvas-save-blocks'), [
            'page_key' => $pageKey,
            'blocks' => $blockOf(['ویژگی ج', 'ویژگی الف', 'ویژگی ب']),
        ])->assertOk();

        $draftAfterReorder = json_decode(Setting::get('canvas_draft_data'), true);
        $reorderedFeatures = array_column($draftAfterReorder['pages'][$pageKey]['blocks'][0]['props']['plans'][0]['features'], 'text');
        $this->assertSame(['ویژگی ج', 'ویژگی الف', 'ویژگی ب'], $reorderedFeatures, 'reorder must persist the new order exactly');

        // 4. PUBLISH — moves draft into the live, public-facing revision.
        $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'))->assertOk()->assertJson(['success' => true]);

        // 5. PUBLIC RENDER — the real public route, in the reordered sequence.
        $response = $this->get(route('page.show', $pageKey));
        $response->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('ویژگی الف', $html);
        $this->assertStringContainsString('ویژگی ب', $html);
        $this->assertStringContainsString('ویژگی ج', $html);
        $this->assertStringNotContainsString('[{"text"', $html, 'no raw JSON must ever leak into public output');

        $posC = strpos($html, 'ویژگی ج');
        $posA = strpos($html, 'ویژگی الف');
        $posB = strpos($html, 'ویژگی ب');
        $this->assertTrue($posC !== false && $posA !== false && $posB !== false);
        $this->assertTrue($posC < $posA && $posA < $posB, 'public render must reflect the reordered sequence, not the original authoring order');
    }

    public function test_pricing_cards_features_schema_declares_a_bounded_nested_repeater_with_no_json_exposed(): void
    {
        $schema = \App\Services\ThemeCanvas\BlockRegistry::all()['pricing_cards']['schema']['plans']['fields']['features'] ?? null;

        $this->assertNotNull($schema);
        $this->assertSame('repeater', $schema['type'], 'features must be a real nested repeater, not a raw textarea exposing JSON');
        $this->assertArrayHasKey('text', $schema['fields'] ?? [], 'each feature must have a plain text field, not a JSON blob');
    }

    public function test_bento_grid_renders_authored_array(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'bg1', 'type' => 'bento_grid',
            'props' => ['items' => [['title' => 'آیتم تستی', 'text' => 'متن آیتم', 'icon' => '⭐', 'size' => 'large', 'bg_color' => '#000', 'text_color' => '#fff']]],
        ]);

        $this->assertStringContainsString('آیتم تستی', $html);
    }

    public function test_services_cards_renders_authored_array(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'svc1', 'type' => 'services_cards',
            'props' => ['services' => [['icon' => '🛠️', 'title' => 'خدمت تستی', 'text' => 'توضیح خدمت', 'link' => '#', 'link_text' => 'ادامه']]],
        ]);

        $this->assertStringContainsString('خدمت تستی', $html);
    }

    public function test_features_grid_renders_authored_array(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'fg1', 'type' => 'features_grid',
            'props' => ['features' => [['icon' => '✅', 'title' => 'ویژگی تستی', 'text' => 'توضیح ویژگی']]],
        ]);

        $this->assertStringContainsString('ویژگی تستی', $html);
    }
}
