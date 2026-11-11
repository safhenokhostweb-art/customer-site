<?php

namespace Tests\Feature\ThemeCanvas;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * AI Dev HUD (partials/ai-dev-hud): a read-only client-side tool, wired into
 * the customer Canvas editor at three spots only, and switched off on the
 * server when the site setting `enable_ai_dev_hud` is set and false.
 */
class AiDevHudTest extends TestCase
{
    use RefreshDatabase;

    private function render(string $part): string
    {
        return view('admin.theme-design.partials.ai-dev-hud', ['part' => $part])->render();
    }

    public function test_without_the_setting_the_hud_ships_and_waits_for_its_trigger(): void
    {
        $html = $this->render('toolbar');
        $this->assertStringContainsString('root.TbDevHud = Object.freeze(', $html);
        $this->assertStringContainsString("get('ai_dev') === '1'", $html, '?ai_dev=1 turns it on');
        $this->assertStringContainsString("e.code === 'KeyD'", $html, 'Ctrl+Shift+D on the physical key (Persian layout)');
        $this->assertStringContainsString('placeholder="دستور رفع باگ یا افزودن امکان جدید..."', $html);
        $this->assertStringContainsString('کپی پرامپت هوش مصنوعی (AI Prompt)', $html);
        $this->assertStringContainsString('active: false,', $html, 'off until triggered');

        // Read-only: no request of its own, no external origin.
        foreach (['fetch(', 'XMLHttpRequest', 'sendBeacon', 'https://'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $html, $forbidden);
        }
        $this->assertStringContainsString('data-ai-dev-health', $this->render('health'));
    }

    public function test_the_renderer_map_names_the_files_that_render_a_type(): void
    {
        $html = $this->render('toolbar');
        preg_match('/var RENDERERS = (\{.*?\});/s', $html, $m);
        $map = json_decode($m[1] ?? '{}', true);
        $this->assertContains('renderSection', $map['package'] ?? []);
        $this->assertContains('renderBlock', $map['host'] ?? [], 'methods the host itself declares');
        $this->assertNotContains('renderNavHeader', $map['host'] ?? [], 'inherited methods are the package\'s');
        $this->assertContains('renderModuleBlock', $map['module'] ?? []);
    }

    public function test_the_children_policy_ships_the_servers_own_rules(): void
    {
        $html = $this->render('toolbar');
        preg_match('/var POLICY = (\{.*?\});\n/s', $html, $m);
        $policy = json_decode($m[1] ?? '{}', true);
        $this->assertSame(\SaaSPlatform\ThemeRuntime\Layout\DocumentValidator::MAX_DEPTH, $policy['maxDepth'] ?? null);
        $types = $policy['types'] ?? [];
        $this->assertSame(['k' => 'section', 'c' => 1, 'r' => 1], $types['section'] ?? null, 'container, children validated on save');
        $this->assertSame('container', $types['flex_container']['k'] ?? null, 'LayoutContract::kindForType');
        $this->assertSame(0, $types['heading']['c'] ?? null, 'widgets are leaves');
        $this->assertSame(['k' => 'container', 'c' => 1, 'r' => 0], $types['tabs'] ?? null, 'editor container whose children the save validator skips');

        foreach (["function childrenPolicy(", "'[slot-error]'", "'[children-policy]'", 'ساختار فرزندان (children_structure)', 'data-ai-dev-children'] as $needle) {
            $this->assertStringContainsString($needle, $html . $this->render('health'), $needle);
        }
    }

    public function test_content_gaps_are_split_from_code_issues_with_a_deep_link(): void
    {
        $html = $this->render('toolbar') . $this->render('health');
        foreach ([
            'function contentIssues(', 'function deadLinksIn(', 'function requiredPropIssue(', 'focusField(field) {',
            "f.code !== 'DEAD_LINK'",                                  // the HUD's DEAD_LINK is content, not core
            '- **نواقص محتوایی قابل تنظیم توسط مدیر:**',
            'برایشان سورس PHP/CSS را تغییر نده، مگر اینکه اسکیما یا بایندینگ همان فیلد خراب باشد.',
            'data-ai-dev-fix-field', 'data-ai-dev-content', 'data-ai-dev-core', 'تنظیم فیلد در پنل ادیتور',
        ] as $needle) {
            $this->assertStringContainsString($needle, $html, $needle);
        }
        preg_match('/var NETWORKS = (\{.*?\});\n/s', $html, $m);
        $this->assertSame(\SaaSPlatform\ThemeRuntime\Support\SocialNetworks::label('bale'), json_decode($m[1] ?? '{}', true)['bale'] ?? null, 'messages name a network as the site does');
    }

    public function test_the_add_child_modal_is_searchable(): void
    {
        $editor = (string) file_get_contents(resource_path('views/admin/theme-design/canvas-editor.blade.php'));
        $this->assertStringContainsString('<input type="search" x-ref="childSearch" x-model="childQuery"', $editor);
        $this->assertStringContainsString('data-testid="tb-add-child-search-clear"', $editor);
        $this->assertStringContainsString('ویجتی با این عنوان یافت نشد', $editor);
        $this->assertStringContainsString("x-show=\"childMatches('{{ \$blockType }}')\"", $editor);
        $this->assertStringContainsString('@keydown.escape.window="if (showAddChildModal) showAddChildModal = false"', $editor);
    }

    /** @return array<string, array{mixed}> */
    public static function offValues(): array
    {
        return ['false' => [false], 'zero' => ['0'], 'empty (unchecked form field)' => ['']];
    }

    #[DataProvider('offValues')]
    public function test_a_site_that_turns_it_off_gets_nothing(mixed $value): void
    {
        Setting::set('enable_ai_dev_hud', $value);
        $this->assertSame('', trim($this->render('toolbar')));
        $this->assertSame('', trim($this->render('health')));
    }

    public function test_a_site_that_turns_it_on_gets_it(): void
    {
        Setting::set('enable_ai_dev_hud', '1');
        $this->assertStringContainsString('root.TbDevHud', $this->render('toolbar'));
    }

    public function test_the_editor_wires_it_at_three_spots_only(): void
    {
        $editor = (string) file_get_contents(resource_path('views/admin/theme-design/canvas-editor.blade.php'));
        $this->assertSame(1, substr_count($editor, "@include('admin.theme-design.partials.ai-dev-hud', ['part' => 'toolbar'])"));
        $this->assertSame(1, substr_count($editor, "@include('admin.theme-design.partials.ai-dev-hud', ['part' => 'health'])"));
        $this->assertSame(1, substr_count($editor, 'window.TbDevHud?.attach(this, { frame: () => this.getIframe() });'));
        // Nothing else in the editor knows about it.
        $this->assertSame(1, substr_count($editor, 'TbDevHud'));
        $this->assertSame(2, substr_count($editor, 'partials.ai-dev-hud'));
    }
}
