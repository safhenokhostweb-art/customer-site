<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Setting;
use App\Models\ThemeInstanceRevision;
use App\Models\User;
use App\Services\ThemeCanvas\ThemeInstanceRevisionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bento Grid save → reload → re-render (owner report: "invalid after save
 * and reload"). The production cause was ADR-072 ("Array to string
 * conversion" on a Media Library bg_image, last logged 2026-11-01 05:47).
 * This proves the whole stored shape survives the real editor chain:
 * save endpoint (with Laravel's empty-string → null and trim middleware),
 * the persisted draft, the editor's render-block call on the reloaded
 * block, a second save of that reloaded block, publish, and the public
 * page — with no [invalid]/[error] placeholder anywhere.
 */
class BentoGridSaveReloadRoundTripTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        file_put_contents(storage_path('installed.lock'), '1');
        config(['site.owner_site' => true, 'site.platform_site_id' => 601]);
    }

    /** One card of every kind, with every field shape the Inspector writes. */
    private function bentoBlock(): array
    {
        return [
            'id' => 'bento-rt-1',
            'type' => 'bento_grid',
            'props' => [
                'title' => 'بنتو رفت‌وبرگشت',
                'items' => [
                    [
                        '_id' => 'item_content', 'kind' => 'content', 'title' => 'کارت محتوا', 'text' => 'متن کارت',
                        'icon' => 'zap', 'size' => 'featured', 'bg_color' => 'var(--tb-primary, #4f46e5)', 'text_color' => '#ffffff',
                        'link' => ['url' => '/contact', 'target' => '_self', 'nofollow' => false, 'sponsored' => false, 'ariaLabel' => null],
                        'image' => '/images/bento-card.png', 'bg_image' => 'https://cdn.example.test/bg.jpg',
                        'bg_color_from' => 'rgba(0, 0, 0, 0.1)', 'bg_color_to' => 'rgba(0, 0, 0, 0.6)',
                        'glow_enabled' => true, 'glow_color' => 'var(--tb-primary, #6366f1)', 'glow_speed' => 2.5,
                    ],
                    [
                        '_id' => 'item_product', 'kind' => 'product', 'title' => 'محصول روز', 'text' => '',
                        'icon' => '', 'size' => 'half', 'bg_color' => '#f8fafc', 'text_color' => '#0f172a',
                        'link' => '', 'image' => '', 'bg_image' => '', 'bg_color_from' => '', 'bg_color_to' => '',
                        'glow_enabled' => false, 'glow_color' => '#6366f1', 'glow_speed' => 2, 'product_id' => '0',
                    ],
                    [
                        '_id' => 'item_countdown', 'kind' => 'countdown', 'title' => 'حراج پاییزه', 'text' => 'تا پایان',
                        'icon' => 'clock', 'size' => 'small', 'bg_color' => '#fef9c3', 'text_color' => '#713f12',
                        'link' => 'https://example.test/sale', 'end_date' => '۱۴۰۵/۰۹/۱۵', 'end_time' => '23:59',
                        'glow_enabled' => '1', 'glow_speed' => '1.5',
                    ],
                    [
                        '_id' => 'item_metric', 'kind' => 'metric', 'title' => 'مشتری راضی', 'text' => '',
                        'icon' => 'users', 'size' => 'tall', 'bg_color' => '#f0fdf4', 'text_color' => '#14532d',
                        'link' => '', 'metric_value' => '12000+', 'glow_enabled' => false,
                    ],
                ],
            ],
        ];
    }

    private function assertRenderedClean(string $html, string $where): void
    {
        // Markup only: the shared stylesheet on a full page defines .tb-placeholder.
        $this->assertDoesNotMatchRegularExpression('/class="tb-placeholder\b/', $html, "{$where}: bento_grid must not render a placeholder");
        $this->assertStringNotContainsString('[invalid]', $html, $where);
        $this->assertStringNotContainsString('[error]', $html, $where);
        $this->assertStringContainsString('tb-bento-grid', $html, $where);
        foreach (['کارت محتوا', 'حراج پاییزه', 'مشتری راضی'] as $title) {
            $this->assertStringContainsString($title, $html, "{$where}: card «{$title}» must render");
        }
    }

    private function draftBlock(string $pageKey): array
    {
        $draft = json_decode(Setting::get('canvas_draft_data'), true);
        return $draft['pages'][$pageKey]['blocks'][0];
    }

    public function test_bento_grid_survives_save_reload_resave_publish_and_public_render(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $pageKey = 'bento-rt-' . uniqid();

        app(ThemeInstanceRevisionService::class)->record([
            'header_blocks' => [], 'footer_blocks' => [],
            'header_variants' => [], 'footer_variants' => [],
            'pages' => [$pageKey => ['slug' => $pageKey, 'title' => 'Bento RT', 'blocks' => []]],
        ], ThemeInstanceRevision::TYPE_INSTALL, ['theme_identifier' => 'bento-rt-theme-' . uniqid()]);

        // 1. SAVE through the real editor endpoint.
        $this->actingAs($admin)->putJson(route('admin.theme-design.canvas-save-blocks'), [
            'page_key' => $pageKey, 'blocks' => [$this->bentoBlock()],
        ])->assertOk()->assertJson(['success' => true]);

        // 2. RELOAD: the stored block keeps its structure (items stay a
        // list of four objects in authored order, the structured link and
        // the kinds survive; '' fields may come back as null).
        $reloaded = $this->draftBlock($pageKey);
        $this->assertSame('bento_grid', $reloaded['type']);
        $this->assertIsArray($reloaded['props']['items']);
        $this->assertTrue(array_is_list($reloaded['props']['items']));
        $this->assertSame(['content', 'product', 'countdown', 'metric'], array_column($reloaded['props']['items'], 'kind'));
        $this->assertSame('/contact', $reloaded['props']['items'][0]['link']['url']);

        // 3. RE-RENDER the reloaded block exactly as the editor does.
        $editorHtml = $this->actingAs($admin)
            ->postJson(route('admin.theme-design.canvas-render-block'), ['block' => $reloaded])
            ->assertOk()->json('html');
        $this->assertRenderedClean($editorHtml, 'editor render after reload');
        $this->assertStringContainsString('tb-bento-pulse-card', $editorHtml, 'glow survives the round trip');
        $this->assertStringContainsString('href="/contact"', $editorHtml, 'structured card link survives the round trip');

        // 4. SAVE AGAIN what the editor reloaded: the stored shape is a
        // fixed point (a second round trip changes nothing).
        $this->actingAs($admin)->putJson(route('admin.theme-design.canvas-save-blocks'), [
            'page_key' => $pageKey, 'blocks' => [$reloaded],
        ])->assertOk()->assertJson(['success' => true]);
        $this->assertSame($reloaded, $this->draftBlock($pageKey), 'save → reload → save must be idempotent');

        // 5. PUBLISH and render the real public page.
        $this->actingAs($admin)->postJson(route('admin.theme-design.canvas-publish'))->assertOk()->assertJson(['success' => true]);
        $public = $this->get(route('page.show', $pageKey))->assertOk()->getContent();
        $this->assertRenderedClean($public, 'public page after publish');
    }

    /**
     * Shapes older saves and imports hold: items as a JSON string, a
     * Media Library pick that no longer resolves, a structured legacy
     * link_url, a numeric or non-text title, and a stray non-object entry.
     *
     * @return array<string, array{0: mixed}>
     */
    public static function legacyItemShapes(): array
    {
        $card = ['title' => 'کارت قدیمی', 'text' => 'متن', 'size' => 'normal', 'bg_color' => '#000', 'text_color' => '#fff'];

        return [
            'json string items' => [json_encode([$card], JSON_UNESCAPED_UNICODE)],
            'unresolved media picks' => [[$card + ['image' => ['media_id' => '00000000-0000-4000-8000-000000000000'], 'bg_image' => ['media_id' => '00000000-0000-4000-8000-000000000001']]]],
            'structured legacy link_url' => [[$card + ['link' => '', 'link_url' => ['url' => '/legacy', 'target' => '_self'], 'link_label' => 'بیشتر']]],
            'numeric title' => [[['title' => 1405] + $card, $card]],
            'non-text title on an image card' => [[['title' => ['fa' => 'کارت']] + $card + ['image' => '/images/bento-card.png'], $card]],
            'stray non-object item' => [[$card, 'not-a-card', null]],
        ];
    }

    /** @dataProvider legacyItemShapes */
    public function test_bento_grid_renders_every_stored_legacy_shape_without_a_placeholder(mixed $items): void
    {
        $renderer = app(\App\Services\ThemeCanvas\ThemeCanvasRenderer::class);
        $renderer->setThemeData(['pages' => []]);
        $renderer->setContext(['editor_mode' => true]);

        $html = $renderer->renderBlock(['id' => 'bento-legacy', 'type' => 'bento_grid', 'props' => ['title' => 'قدیمی', 'items' => $items]]);

        $this->assertDoesNotMatchRegularExpression('/class="tb-placeholder\b/', $html);
        $this->assertStringContainsString('tb-bento-grid', $html);
        $this->assertStringContainsString('tb-bento-item', $html);
    }
}
