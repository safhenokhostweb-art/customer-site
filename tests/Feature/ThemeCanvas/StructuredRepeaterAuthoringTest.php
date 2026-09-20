<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\License;
use App\Models\Setting;
use App\Models\User;
use App\Services\ThemeCanvas\BlockRegistry;
use App\Services\ThemeCanvas\ThemeCanvasRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BLD-11..13 (Full Product Commercial Audit, TASK 2A-CONTINUATION): the 7
 * canonical block types below (10 fields total) carried genuinely
 * incomplete array authoring on the Canvas editor — a raw, non-repeater
 * field with no real add/remove/reorder UI. `App\Services\ThemeCanvas\
 * BlockRegistry` now declares local `type: repeater` overrides for each
 * (field names verified directly against the actual renderer read paths in
 * `SaaSPlatform\ThemeRuntime\BlockRenderer` / this host's own overrides —
 * not guessed), and the existing Canvas editor's Alpine repeater adapter
 * was extended with one bounded extra nesting level for the one genuinely
 * nested case, `nav_footer.columns[].links[]`.
 *
 * Each element is proven through the full required chain: STATIC SCHEMA →
 * LIVE AUTHORING UI (canvas-edit response) → SAVE → RELOAD (canvas-save-
 * blocks + draft re-read) → PUBLIC RENDER (real ThemeCanvasRenderer /
 * live home-page route), matching the same real pipeline
 * HeaderFooterVariantSelectionTest already proves for header/footer chrome.
 */
class StructuredRepeaterAuthoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true]);
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('installed.lock'));
        parent::tearDown();
    }

    private function admin(): User
    {
        License::create([
            'license_key' => 'REPEAT-' . uniqid(),
            'domain' => request()->getHost(),
            'platform_valid' => true,
            'expires_at' => now()->addYear(),
            'last_validated_at' => now(),
            'modules' => ['shop', 'blog'],
        ]);

        return User::factory()->create(['is_admin' => true]);
    }

    private function renderer(): ThemeCanvasRenderer
    {
        $renderer = app(ThemeCanvasRenderer::class);
        $renderer->setThemeData(['pages' => []]);
        return $renderer;
    }

    // ── A. STATIC SCHEMA: every candidate field is now a real repeater ────

    public function test_every_candidate_field_is_declared_as_a_repeater_with_correct_fields(): void
    {
        $registry = BlockRegistry::all();

        $navHeader = $registry['nav_header']['schema']['nav_links'];
        self::assertSame('repeater', $navHeader['type']);
        self::assertSame(['label', 'url', 'mega_columns', 'mega_banner_image', 'mega_banner_url', 'mega_banner_text', 'mega_show_desktop', 'mega_mobile_behavior'], array_keys($navHeader['fields']));
        // Commercial Productization fase-2: Mega Menu's columns are a real,
        // independent nested repeater (columns[].items[]) — not inferred by
        // matching duplicate column-title text.
        $megaColumns = $navHeader['fields']['mega_columns'];
        self::assertSame('repeater', $megaColumns['type']);
        self::assertSame(['title', 'items'], array_keys($megaColumns['fields']));
        self::assertSame('repeater', $megaColumns['fields']['items']['type']);
        self::assertSame(['label', 'url', 'icon', 'image'], array_keys($megaColumns['fields']['items']['fields']));

        $navFooter = $registry['nav_footer']['schema'];
        self::assertSame('repeater', $navFooter['columns']['type']);
        self::assertSame(['title', 'links'], array_keys($navFooter['columns']['fields']));
        self::assertSame('repeater', $navFooter['columns']['fields']['links']['type']);
        self::assertSame(['label', 'url'], array_keys($navFooter['columns']['fields']['links']['fields']));
        self::assertSame('repeater', $navFooter['links']['type']);
        self::assertSame(['label', 'url'], array_keys($navFooter['links']['fields']));
        self::assertSame('repeater', $navFooter['social_links']['type']);
        self::assertSame(['url', 'label'], array_keys($navFooter['social_links']['fields']));

        $about = $registry['about_section']['schema'];
        self::assertSame('repeater', $about['features']['type']);
        self::assertSame(['label'], array_keys($about['features']['fields']));
        self::assertSame('repeater', $about['stats']['type']);
        self::assertSame(['value', 'label'], array_keys($about['stats']['fields']));

        $service = $registry['service_cards']['schema']['cards'];
        self::assertSame('repeater', $service['type']);
        self::assertSame(['icon', 'title', 'description', 'url'], array_keys($service['fields']));

        $project = $registry['project_cards']['schema']['cards'];
        self::assertSame('repeater', $project['type']);
        self::assertSame(['category', 'title', 'description', 'url'], array_keys($project['fields']));

        $contact = $registry['contact_info']['schema']['items'];
        self::assertSame('repeater', $contact['type']);
        self::assertSame(['icon', 'label', 'value'], array_keys($contact['fields']));

        $faq = $registry['faq']['schema']['items'];
        self::assertSame('repeater', $faq['type']);
        self::assertSame(['question', 'answer'], array_keys($faq['fields']));
    }

    // ── B. LIVE AUTHORING UI: canvas-edit actually serves the repeater UI ─

    public function test_canvas_editor_page_exposes_the_repeater_schema_and_nested_adapter_markup(): void
    {
        Setting::set('active_canvas_theme_data', json_encode(['pages' => ['home' => ['blocks' => []]]]), 'theme');

        $response = $this->actingAs($this->admin())->get(route('admin.theme-design.canvas-edit'));

        $response->assertOk();
        $response->assertSee('"nav_links":{"type":"repeater"', false);
        $response->assertSee('"columns":{"type":"repeater"', false);
        $response->assertSee('tb-nested-repeater-', false);
        $response->assertSee('nestedItems(propKey, itemIndex, fieldKey)', false);
    }

    // ── C. SAVE → RELOAD: structured data (incl. one bounded nested level) survives exactly ─

    public function test_structured_field_data_including_nested_footer_columns_survives_save_and_reload(): void
    {
        $admin = $this->admin();
        Setting::set('active_canvas_theme_data', json_encode(['pages' => ['home' => ['blocks' => []]]]), 'theme');

        $blocks = [
            [
                'id' => 'blk_nav_header', 'type' => 'nav_header',
                'props' => ['nav_links' => [
                    ['label' => 'خانه', 'url' => '/'],
                    ['label' => 'درباره ما', 'url' => '/about'],
                ]],
            ],
            [
                'id' => 'blk_nav_footer', 'type' => 'nav_footer',
                'props' => ['columns' => [
                    ['title' => 'محصولات', 'links' => [
                        ['label' => 'محصول ۱', 'url' => '/p1'],
                        ['label' => 'محصول ۲', 'url' => '/p2'],
                    ]],
                ]],
            ],
        ];

        $this->actingAs($admin)->putJson(route('admin.theme-design.canvas-save-blocks'), [
            'page_key' => 'home',
            'blocks' => $blocks,
        ])->assertOk();

        $draft = json_decode(Setting::get('canvas_draft_data'), true);
        $savedBlocks = $draft['pages']['home']['blocks'];

        self::assertSame([
            ['label' => 'خانه', 'url' => '/'],
            ['label' => 'درباره ما', 'url' => '/about'],
        ], $savedBlocks[0]['props']['nav_links']);

        self::assertSame('محصولات', $savedBlocks[1]['props']['columns'][0]['title']);
        self::assertSame([
            ['label' => 'محصول ۱', 'url' => '/p1'],
            ['label' => 'محصول ۲', 'url' => '/p2'],
        ], $savedBlocks[1]['props']['columns'][0]['links']);

        // RELOAD: re-open the editor and confirm the same nested structure
        // is what actually gets embedded for the client to resume editing.
        // The page embeds INITIAL_PAGES via Blade's @json() (default
        // options, so non-ASCII is \uXXXX-escaped) — compare against that
        // same encoding rather than the raw Persian text.
        $reload = $this->actingAs($admin)->get(route('admin.theme-design.canvas-edit'));
        $reload->assertOk();
        $reload->assertSee(trim(json_encode('محصول ۱'), '"'), false);
        $reload->assertSee(trim(json_encode('محصول ۲'), '"'), false);
    }

    // ── D. PUBLIC RENDER: each element's authored data reaches real output ─

    public function test_nav_header_links_reach_the_public_render(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'nh1', 'type' => 'nav_header',
            'props' => ['nav_links' => [['label' => 'MARKER-NAVLINK-ONE', 'url' => '/one']]],
        ]);
        $this->assertStringContainsString('MARKER-NAVLINK-ONE', $html);
        $this->assertStringContainsString('/one', $html);
    }

    public function test_nav_footer_nested_columns_and_social_links_reach_the_public_render(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'nf1', 'type' => 'nav_footer',
            'props' => [
                'columns' => [['title' => 'MARKER-COL-TITLE', 'links' => [['label' => 'MARKER-COL-LINK', 'url' => '/col-link']]]],
                'links' => [['label' => 'MARKER-LEGAL-LINK', 'url' => '/legal']],
                'social_links' => [['url' => 'https://x.example/handle', 'label' => 'MARKER-SOCIAL']],
            ],
        ]);
        $this->assertStringContainsString('MARKER-COL-TITLE', $html);
        $this->assertStringContainsString('MARKER-COL-LINK', $html);
        $this->assertStringContainsString('MARKER-LEGAL-LINK', $html);
        $this->assertStringContainsString('MARKER-SOCIAL', $html);
    }

    public function test_about_section_features_and_stats_reach_the_public_render(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'as1', 'type' => 'about_section',
            'props' => [
                'features' => [['label' => 'MARKER-FEATURE']],
                'stats' => [['value' => '99', 'label' => 'MARKER-STAT']],
            ],
        ]);
        $this->assertStringContainsString('MARKER-FEATURE', $html);
        $this->assertStringContainsString('99', $html);
        $this->assertStringContainsString('MARKER-STAT', $html);
    }

    public function test_service_cards_reach_the_public_render(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'sc1', 'type' => 'service_cards',
            'props' => ['cards' => [['icon' => '🔧', 'title' => 'MARKER-SERVICE-TITLE', 'description' => 'MARKER-SERVICE-DESC', 'url' => '/svc']]],
        ]);
        $this->assertStringContainsString('MARKER-SERVICE-TITLE', $html);
        $this->assertStringContainsString('MARKER-SERVICE-DESC', $html);
        $this->assertStringContainsString('/svc', $html);
    }

    public function test_project_cards_reach_the_public_render(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'pc1', 'type' => 'project_cards',
            'props' => ['cards' => [['category' => 'MARKER-CATEGORY', 'title' => 'MARKER-PROJECT-TITLE', 'description' => 'MARKER-PROJECT-DESC', 'url' => '/proj']]],
        ]);
        $this->assertStringContainsString('MARKER-CATEGORY', $html);
        $this->assertStringContainsString('MARKER-PROJECT-TITLE', $html);
        $this->assertStringContainsString('MARKER-PROJECT-DESC', $html);
    }

    public function test_contact_info_items_reach_the_public_render(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'ci1', 'type' => 'contact_info',
            'props' => ['items' => [['icon' => '📞', 'label' => 'MARKER-CONTACT-LABEL', 'value' => 'MARKER-CONTACT-VALUE']]],
        ]);
        $this->assertStringContainsString('MARKER-CONTACT-LABEL', $html);
        $this->assertStringContainsString('MARKER-CONTACT-VALUE', $html);
    }

    public function test_faq_items_reach_the_public_render(): void
    {
        $html = $this->renderer()->renderBlock([
            'id' => 'fq1', 'type' => 'faq',
            'props' => ['items' => [['question' => 'MARKER-QUESTION', 'answer' => 'MARKER-ANSWER']]],
        ]);
        $this->assertStringContainsString('MARKER-QUESTION', $html);
        $this->assertStringContainsString('MARKER-ANSWER', $html);
    }

    // ── E. Full end-to-end chain through the real live public route ───────

    public function test_faq_authored_via_save_reaches_the_live_public_homepage(): void
    {
        $admin = $this->admin();
        Setting::set('active_canvas_theme_data', json_encode([
            'pages' => ['home' => ['is_home' => true, 'blocks' => []]],
        ]), 'theme');

        $this->actingAs($admin)->putJson(route('admin.theme-design.canvas-save-blocks'), [
            'page_key' => 'home',
            'blocks' => [[
                'id' => 'blk_faq', 'type' => 'faq',
                'props' => ['title' => 'سوالات', 'items' => [
                    ['question' => 'MARKER-LIVE-QUESTION', 'answer' => 'MARKER-LIVE-ANSWER'],
                ]],
            ]],
        ])->assertOk();

        $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'))->assertOk();

        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee('MARKER-LIVE-QUESTION', false);
        $response->assertSee('MARKER-LIVE-ANSWER', false);
    }
}
