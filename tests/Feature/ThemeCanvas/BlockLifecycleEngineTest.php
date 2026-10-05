<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Setting;
use App\Services\ThemeCanvas\ThemeRuntimeDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\EnsuresInstallationLock;
use Tests\TestCase;

class BlockLifecycleEngineTest extends TestCase
{
    use EnsuresInstallationLock, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureInstallationLock();
        config([
            'site.owner_site' => true,
            'services.customer_provision_secret' => 'phase8-test-secret',
        ]);
    }

    protected function tearDown(): void
    {
        $this->releaseInstallationLock();
        parent::tearDown();
    }

    public function test_public_runtime_emits_block_props_attribute(): void
    {
        Setting::set('active_canvas_theme_data', json_encode([
            'theme_id' => 'lifecycle-test',
            'theme_name' => 'Lifecycle Test',
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => [
                'home' => ['title' => 'Home', 'slug' => '', 'is_home' => true, 'blocks' => []],
                'probe' => [
                    'title' => 'Lifecycle probe',
                    'slug' => 'lifecycle-probe',
                    'blocks' => [
                        ['id' => 'slider-1', 'type' => 'slider', 'props' => [
                            'slides' => [['image' => 'test.png', 'caption' => 'Test']],
                            'autoplay' => true,
                        ]],
                    ],
                ],
            ],
            'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');

        Setting::set('active_canvas_theme_name', 'Lifecycle Test', 'theme');

        $html = $this->get('/page/lifecycle-probe')->assertOk()->getContent();

        $this->assertStringContainsString('data-tb-block-props=', $html,
            'public runtime mode must embed block props as a JSON attribute');
    }

    public function test_theme_canvas_runtime_exposes_register_block_and_init_block(): void
    {
        Setting::set('active_canvas_theme_data', json_encode([
            'theme_id' => 'lifecycle-test',
            'theme_name' => 'Lifecycle Test',
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => [
                'home' => ['title' => 'Home', 'slug' => '', 'is_home' => true, 'blocks' => []],
                'probe' => [
                    'title' => 'Lifecycle probe',
                    'slug' => 'lifecycle-probe',
                    'blocks' => [
                        ['id' => 'text-1', 'type' => 'text', 'props' => ['content' => 'hello']],
                    ],
                ],
            ],
            'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');

        Setting::set('active_canvas_theme_name', 'Lifecycle Test', 'theme');

        $html = $this->get('/page/lifecycle-probe')->assertOk()->getContent();

        $this->assertStringContainsString('registerBlock', $html,
            'ThemeCanvasRuntime must expose registerBlock API');
        $this->assertStringContainsString('initBlock', $html,
            'ThemeCanvasRuntime must expose initBlock API');
        $this->assertStringContainsString('trackScript', $html,
            'ThemeCanvasRuntime must expose trackScript for asset-load synchronization');
    }

    public function test_editor_mode_does_not_emit_block_props_attribute(): void
    {
        Setting::set('active_canvas_theme_data', json_encode([
            'theme_id' => 'lifecycle-test',
            'theme_name' => 'Lifecycle Test',
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => [
                'home' => ['title' => 'Home', 'slug' => '', 'is_home' => true, 'blocks' => []],
                'probe' => [
                    'title' => 'Lifecycle probe',
                    'slug' => 'lifecycle-probe',
                    'blocks' => [
                        ['id' => 'text-1', 'type' => 'text', 'props' => ['content' => 'hello']],
                    ],
                ],
            ],
            'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');

        Setting::set('active_canvas_theme_name', 'Lifecycle Test', 'theme');

        $document = app(ThemeRuntimeDocument::class)
            ->forMode(ThemeRuntimeDocument::MODE_EDITOR)
            ->build(
                json_decode(Setting::get('active_canvas_theme_data'), true),
                [['id' => 'text-1', 'type' => 'text', 'props' => ['content' => 'hello']]],
                'Test',
                'probe',
            );

        $this->assertStringNotContainsString(
            'data-tb-block-props=',
            $document['contentHtml'],
            'editor mode must not embed block props — editor gets props via RPC'
        );
    }

    public function test_hydration_engine_delegates_to_init_block_after_asset_injection(): void
    {
        Setting::set('active_canvas_theme_data', json_encode([
            'theme_id' => 'lifecycle-test',
            'theme_name' => 'Lifecycle Test',
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => [
                'home' => ['title' => 'Home', 'slug' => '', 'is_home' => true, 'blocks' => []],
                'probe' => [
                    'title' => 'Lifecycle probe',
                    'slug' => 'lifecycle-probe',
                    'blocks' => [
                        ['id' => 'text-1', 'type' => 'text', 'props' => ['content' => 'hello']],
                    ],
                ],
            ],
            'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');

        Setting::set('active_canvas_theme_name', 'Lifecycle Test', 'theme');

        $html = $this->get('/page/lifecycle-probe')->assertOk()->getContent();

        $this->assertStringContainsString(
            'window.ThemeCanvasRuntime.initBlock(el)',
            $html,
            'the hydration engine must call initBlock after asset injection'
        );
    }

    public function test_init_block_is_called_via_microtask_not_blocking(): void
    {
        Setting::set('active_canvas_theme_data', json_encode([
            'theme_id' => 'lifecycle-test',
            'theme_name' => 'Lifecycle Test',
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => [
                'home' => ['title' => 'Home', 'slug' => '', 'is_home' => true, 'blocks' => []],
                'probe' => [
                    'title' => 'Lifecycle probe',
                    'slug' => 'lifecycle-probe',
                    'blocks' => [
                        ['id' => 'text-1', 'type' => 'text', 'props' => ['content' => 'hello']],
                    ],
                ],
            ],
            'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');

        Setting::set('active_canvas_theme_name', 'Lifecycle Test', 'theme');

        $html = $this->get('/page/lifecycle-probe')->assertOk()->getContent();

        $this->assertStringContainsString('Promise.all', $html,
            'init must be scheduled via Promise chain (non-blocking)');
        $this->assertStringContainsString('trackScript', $html,
            'script load must be tracked before init');
    }

    public function test_init_block_dispatches_theme_block_ready_event(): void
    {
        Setting::set('active_canvas_theme_data', json_encode([
            'theme_id' => 'lifecycle-test',
            'theme_name' => 'Lifecycle Test',
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => [
                'home' => ['title' => 'Home', 'slug' => '', 'is_home' => true, 'blocks' => []],
                'probe' => [
                    'title' => 'Lifecycle probe',
                    'slug' => 'lifecycle-probe',
                    'blocks' => [
                        ['id' => 'text-1', 'type' => 'text', 'props' => ['content' => 'hello']],
                    ],
                ],
            ],
            'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');

        Setting::set('active_canvas_theme_name', 'Lifecycle Test', 'theme');

        $html = $this->get('/page/lifecycle-probe')->assertOk()->getContent();

        $this->assertStringContainsString('tb-block-ready', $html,
            'init must add tb-block-ready class on successful completion');
    }

    // ─── Phase 8 Step 3: Event Bus Tests ──────────────────────────────

    public function test_event_bus_api_is_exposed_on_theme_canvas_runtime(): void
    {
        Setting::set('active_canvas_theme_data', json_encode([
            'theme_id' => 'lifecycle-test',
            'theme_name' => 'Lifecycle Test',
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => [
                'home' => ['title' => 'Home', 'slug' => '', 'is_home' => true, 'blocks' => []],
                'probe' => [
                    'title' => 'Lifecycle probe',
                    'slug' => 'lifecycle-probe',
                    'blocks' => [
                        ['id' => 'text-1', 'type' => 'text', 'props' => ['content' => 'hello']],
                    ],
                ],
            ],
            'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');

        Setting::set('active_canvas_theme_name', 'Lifecycle Test', 'theme');

        $html = $this->get('/page/lifecycle-probe')->assertOk()->getContent();

        $this->assertStringContainsString('events: {', $html,
            'ThemeCanvasRuntime must expose an events property on the object');
        $this->assertStringContainsString('on: onEvent', $html,
            'events.on must be exposed');
        $this->assertStringContainsString('off: offEvent', $html,
            'events.off must be exposed');
        $this->assertStringContainsString('emit: emitEvent', $html,
            'events.emit must be exposed');
    }

    public function test_init_context_provides_on_off_emit_methods(): void
    {
        Setting::set('active_canvas_theme_data', json_encode([
            'theme_id' => 'lifecycle-test',
            'theme_name' => 'Lifecycle Test',
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => [
                'home' => ['title' => 'Home', 'slug' => '', 'is_home' => true, 'blocks' => []],
                'probe' => [
                    'title' => 'Lifecycle probe',
                    'slug' => 'lifecycle-probe',
                    'blocks' => [
                        ['id' => 'text-1', 'type' => 'text', 'props' => ['content' => 'hello']],
                    ],
                ],
            ],
            'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');

        Setting::set('active_canvas_theme_name', 'Lifecycle Test', 'theme');

        $html = $this->get('/page/lifecycle-probe')->assertOk()->getContent();

        $this->assertStringContainsString("on: function (eventName, handler)", $html,
            'init context must provide ctx.on');
        $this->assertStringContainsString("off: function (eventName, handler)", $html,
            'init context must provide ctx.off');
        $this->assertStringContainsString("emit: emitEvent", $html,
            'init context must provide ctx.emit');
    }

    public function test_event_bus_is_scoped_not_on_global_window(): void
    {
        Setting::set('active_canvas_theme_data', json_encode([
            'theme_id' => 'lifecycle-test',
            'theme_name' => 'Lifecycle Test',
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => [
                'home' => ['title' => 'Home', 'slug' => '', 'is_home' => true, 'blocks' => []],
                'probe' => [
                    'title' => 'Lifecycle probe',
                    'slug' => 'lifecycle-probe',
                    'blocks' => [
                        ['id' => 'text-1', 'type' => 'text', 'props' => ['content' => 'hello']],
                    ],
                ],
            ],
            'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');

        Setting::set('active_canvas_theme_name', 'Lifecycle Test', 'theme');

        $html = $this->get('/page/lifecycle-probe')->assertOk()->getContent();

        $this->assertStringContainsString('events: {', $html,
            'ThemeCanvasRuntime must expose an events object');
        $this->assertStringNotContainsString("window.onEvent", $html,
            'onEvent must NOT be exposed on window directly — strict isolation');
        $this->assertStringNotContainsString("window.emitEvent", $html,
            'emitEvent must NOT be exposed on window directly — strict isolation');
    }

    public function test_cleanup_element_is_called_on_destroy(): void
    {
        Setting::set('active_canvas_theme_data', json_encode([
            'theme_id' => 'lifecycle-test',
            'theme_name' => 'Lifecycle Test',
            'header_blocks' => [],
            'footer_blocks' => [],
            'pages' => [
                'home' => ['title' => 'Home', 'slug' => '', 'is_home' => true, 'blocks' => []],
                'probe' => [
                    'title' => 'Lifecycle probe',
                    'slug' => 'lifecycle-probe',
                    'blocks' => [
                        ['id' => 'text-1', 'type' => 'text', 'props' => ['content' => 'hello']],
                    ],
                ],
            ],
            'global_styles' => [],
        ], JSON_UNESCAPED_UNICODE), 'theme');

        Setting::set('active_canvas_theme_name', 'Lifecycle Test', 'theme');

        $html = $this->get('/page/lifecycle-probe')->assertOk()->getContent();

        $this->assertStringContainsString('cleanupElement', $html,
            'cleanupElement function must exist for destroy-phase unsubscribe');
        $this->assertStringContainsString("phase === 'destroy' && root && typeof root.getAttribute === 'function'", $html,
            'lifecycle destroy must call cleanupElement for element roots');
    }
}
